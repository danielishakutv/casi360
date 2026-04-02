<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectBudgetLine;
use App\Services\ReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProjectReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/projects/summary
     */
    public function summary(Request $request)
    {
        $request->validate([
            'format'        => 'nullable|in:csv,excel,pdf',
            'status'        => 'nullable|in:draft,active,on_hold,completed,closed',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Project::with(['department:id,name', 'projectManager:id,name'])
            ->withSum('budgetLines', 'total_cost');

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('date_from'))     $query->whereDate('start_date', '>=', $request->date_from);
        if ($request->filled('date_to'))       $query->whereDate('start_date', '<=', $request->date_to);

        $query->orderByDesc('start_date');

        $mapRow = fn (Project $p) => [
            'project_code'    => $p->project_code,
            'name'            => $p->name,
            'department'      => $p->department?->name ?? '—',
            'manager'         => $p->projectManager?->name ?? '—',
            'start_date'      => $p->start_date?->format('Y-m-d') ?? '—',
            'end_date'        => $p->end_date?->format('Y-m-d') ?? '—',
            'total_budget'    => number_format($p->total_budget, 2),
            'budget_spent'    => number_format($p->budget_lines_sum_total_cost ?? 0, 2),
            'status'          => ucwords(str_replace('_', ' ', $p->status)),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('projects_summary', $request);

            return $this->exportService->export(
                'Projects Summary',
                ['Code', 'Name', 'Department', 'Manager', 'Start', 'End', 'Budget', 'Budget Lines Total', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'department_id', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/projects/{id}/detail
     *
     * Full single-project download — PDF only for rich layout, CSV/Excel for flat data.
     */
    public function detail(Request $request, string $id)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $project = Project::with([
            'department:id,name',
            'projectManager:id,name',
            'donors',
            'partners',
            'teamMembers.employee:id,name',
            'activities',
            'budgetLines.budgetCategory:id,name',
            'projectNotes.creator:id,name',
        ])->findOrFail($id);

        $this->logDownload('project_detail', $request);

        // ── PDF: rich multi-section layout ──
        if ($request->format === 'pdf') {
            $data = [
                'project' => [
                    'name'            => $project->name,
                    'project_code'    => $project->project_code,
                    'department'      => $project->department?->name ?? '—',
                    'project_manager' => $project->projectManager?->name ?? '—',
                    'start_date'      => $project->start_date?->format('Y-m-d') ?? '—',
                    'end_date'        => $project->end_date?->format('Y-m-d') ?? '—',
                    'status'          => ucwords(str_replace('_', ' ', $project->status)),
                    'location'        => $project->location ?? '—',
                    'currency'        => $project->currency ?? 'NGN',
                    'total_budget'    => $project->total_budget,
                    'description'     => $project->description,
                    'objectives'      => $project->objectives,
                ],
                'donors' => $project->donors->map(fn ($d) => [
                    'name'                => $d->name,
                    'type'                => ucfirst($d->type ?? '—'),
                    'email'               => $d->email ?? '—',
                    'contribution_amount' => $d->contribution_amount,
                ])->toArray(),
                'partners' => $project->partners->map(fn ($p) => [
                    'name'           => $p->name,
                    'role'           => ucfirst($p->role ?? '—'),
                    'contact_person' => $p->contact_person ?? '—',
                    'email'          => $p->email ?? '—',
                ])->toArray(),
                'team' => $project->teamMembers->map(fn ($t) => [
                    'employee_name' => $t->employee?->name ?? '—',
                    'role'          => $t->role ?? '—',
                    'start_date'    => $t->start_date?->format('Y-m-d') ?? '—',
                    'end_date'      => $t->end_date?->format('Y-m-d') ?? '—',
                ])->toArray(),
                'activities' => $project->activities->map(fn ($a) => [
                    'title'                 => $a->title,
                    'start_date'            => $a->start_date?->format('Y-m-d') ?? '—',
                    'end_date'              => $a->end_date?->format('Y-m-d') ?? '—',
                    'target_date'           => $a->target_date?->format('Y-m-d') ?? '—',
                    'status'                => ucwords(str_replace('_', ' ', $a->status)),
                    'completion_percentage' => $a->completion_percentage,
                ])->toArray(),
                'budgetLines' => $project->budgetLines->map(fn ($b) => [
                    'category'    => $b->budgetCategory?->name ?? '—',
                    'description' => $b->description ?? '—',
                    'unit'        => $b->unit ?? '—',
                    'quantity'    => $b->quantity,
                    'unit_cost'   => $b->unit_cost,
                    'total_cost'  => $b->total_cost,
                ])->toArray(),
                'notes' => $project->projectNotes->map(fn ($n) => [
                    'title'      => $n->title ?? '—',
                    'content'    => $n->content ?? '—',
                    'author'     => $n->creator?->name ?? '—',
                    'created_at' => $n->created_at->format('Y-m-d'),
                ])->toArray(),
            ];

            $pdf = Pdf::loadView('reports.project-detail', $data)->setPaper('a4', 'portrait');

            $filename = 'project-' . \Illuminate\Support\Str::slug($project->name) . '-' . now()->format('Y-m-d-His');

            return $pdf->download("{$filename}.pdf");
        }

        // ── CSV / Excel: flat activity list with project context ──
        $rows = $project->activities->map(fn ($a) => [
            'project'               => $project->name,
            'project_code'          => $project->project_code,
            'activity'              => $a->title,
            'start_date'            => $a->start_date?->format('Y-m-d') ?? '—',
            'end_date'              => $a->end_date?->format('Y-m-d') ?? '—',
            'target_date'           => $a->target_date?->format('Y-m-d') ?? '—',
            'status'                => ucwords(str_replace('_', ' ', $a->status)),
            'completion_percentage' => $a->completion_percentage . '%',
        ]);

        $filename = 'project-' . \Illuminate\Support\Str::slug($project->name) . '-' . now()->format('Y-m-d-His');

        return $this->exportService->export(
            "Project Detail — {$project->name}",
            ['Project', 'Code', 'Activity', 'Start', 'End', 'Target', 'Status', '% Complete'],
            $rows,
            $request->format,
            ['Project' => $project->name, 'Code' => $project->project_code, 'Generated' => now()->format('d M Y, H:i')]
        );
    }

    /**
     * GET /api/v1/reports/projects/budget-utilization
     */
    public function budgetUtilization(Request $request)
    {
        $request->validate([
            'format'     => 'nullable|in:csv,excel,pdf',
            'project_id' => 'nullable|uuid|exists:projects,id',
        ]);

        $query = ProjectBudgetLine::with([
            'project:id,name,project_code',
            'budgetCategory:id,name',
        ]);

        if ($request->filled('project_id')) $query->where('project_id', $request->project_id);

        $query->orderBy('project_id');

        $mapRow = fn (ProjectBudgetLine $b) => [
            'project'     => $b->project?->name ?? '—',
            'code'        => $b->project?->project_code ?? '—',
            'category'    => $b->budgetCategory?->name ?? '—',
            'description' => $b->description ?? '—',
            'budgeted'    => number_format($b->total_cost, 2),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('projects_budget_utilization', $request);

            return $this->exportService->export(
                'Budget Utilization Report',
                ['Project', 'Code', 'Category', 'Description', 'Budgeted'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['project_id'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    /**
     * GET /api/v1/reports/projects/activity-progress
     */
    public function activityProgress(Request $request)
    {
        $request->validate([
            'format'     => 'nullable|in:csv,excel,pdf',
            'project_id' => 'nullable|uuid|exists:projects,id',
            'status'     => 'nullable|in:not_started,in_progress,completed,delayed,cancelled',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = ProjectActivity::with('project:id,name,project_code');

        if ($request->filled('project_id')) $query->where('project_id', $request->project_id);
        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('date_from'))  $query->whereDate('start_date', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('end_date', '<=', $request->date_to);

        $query->orderBy('project_id')->orderBy('sort_order');

        $mapRow = fn (ProjectActivity $a) => [
            'project'               => $a->project?->name ?? '—',
            'code'                  => $a->project?->project_code ?? '—',
            'activity'              => $a->title,
            'start_date'            => $a->start_date?->format('Y-m-d') ?? '—',
            'end_date'              => $a->end_date?->format('Y-m-d') ?? '—',
            'target_date'           => $a->target_date?->format('Y-m-d') ?? '—',
            'status'                => ucwords(str_replace('_', ' ', $a->status)),
            'completion_percentage' => $a->completion_percentage . '%',
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);
            $this->logDownload('projects_activity_progress', $request);

            return $this->exportService->export(
                'Activity Progress Report',
                ['Project', 'Code', 'Activity', 'Start', 'End', 'Target', 'Status', '% Complete'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['project_id', 'status', 'date_from', 'date_to'])
            );
        }

        return $this->paginated($query, $mapRow, $request);
    }

    // ── Shared Helpers ──────────────────────────────────────

    private function paginated($query, callable $mapRow, Request $request)
    {
        $paginated = $query->paginate(min((int) $request->input('per_page', 25), 100));

        return $this->success([
            'rows' => collect($paginated->items())->map($mapRow),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    private function logDownload(string $report, Request $request): void
    {
        AuditLog::record(
            auth()->id(),
            'report_downloaded',
            'report',
            null,
            null,
            ['report' => $report, 'format' => $request->format, 'filters' => $request->except(['format', 'page', 'per_page'])]
        );
    }

    private function buildMeta(Request $request, array $filterKeys): array
    {
        $meta = ['Generated' => now()->format('d M Y, H:i')];
        foreach ($filterKeys as $key) {
            if ($request->filled($key)) {
                $meta[str_replace('_', ' ', ucfirst($key))] = $request->input($key);
            }
        }
        return $meta;
    }
}
