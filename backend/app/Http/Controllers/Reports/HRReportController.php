<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Services\ReportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HRReportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    /**
     * GET /api/v1/reports/hr/employees
     *
     * Employee directory report.
     */
    public function employees(Request $request)
    {
        $request->validate([
            'format'         => 'nullable|in:csv,excel,pdf',
            'department_id'  => 'nullable|uuid|exists:departments,id',
            'designation_id' => 'nullable|uuid|exists:designations,id',
            'status'         => 'nullable|in:active,on_leave,terminated',
            'gender'         => 'nullable|in:male,female,other',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Employee::with(['department:id,name', 'designation:id,name']);

        if ($request->filled('department_id'))  $query->where('department_id', $request->department_id);
        if ($request->filled('designation_id')) $query->where('designation_id', $request->designation_id);
        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('gender'))         $query->where('gender', $request->gender);
        if ($request->filled('date_from'))      $query->whereDate('join_date', '>=', $request->date_from);
        if ($request->filled('date_to'))        $query->whereDate('join_date', '<=', $request->date_to);

        $query->orderBy('name');

        if ($request->filled('format')) {
            $rows = $query->get()->map(fn (Employee $e) => [
                'staff_id'    => $e->staff_id,
                'name'        => $e->name,
                'email'       => $e->email,
                'phone'       => $e->phone,
                'department'  => $e->department?->name ?? '—',
                'designation' => $e->designation?->name ?? '—',
                'gender'      => ucfirst($e->gender ?? '—'),
                'join_date'   => $e->join_date?->format('Y-m-d') ?? '—',
                'status'      => ucfirst($e->status),
            ]);

            $this->logDownload('hr_employees', $request);

            return $this->exportService->export(
                'Employee Directory',
                ['Staff ID', 'Name', 'Email', 'Phone', 'Department', 'Designation', 'Gender', 'Join Date', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['department_id', 'designation_id', 'status', 'gender', 'date_from', 'date_to'])
            );
        }

        $paginated = $query->paginate(min((int) $request->input('per_page', 25), 100));

        return $this->success([
            'rows' => collect($paginated->items())->map(fn (Employee $e) => [
                'staff_id'    => $e->staff_id,
                'name'        => $e->name,
                'email'       => $e->email,
                'phone'       => $e->phone,
                'department'  => $e->department?->name ?? '—',
                'designation' => $e->designation?->name ?? '—',
                'gender'      => ucfirst($e->gender ?? '—'),
                'join_date'   => $e->join_date?->format('Y-m-d') ?? '—',
                'status'      => ucfirst($e->status),
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/reports/hr/departments
     *
     * Department summary report.
     */
    public function departments(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:csv,excel,pdf',
            'status' => 'nullable|in:active,inactive',
        ]);

        $query = Department::withCount('employees');

        if ($request->filled('status')) $query->where('status', $request->status);

        $query->orderBy('name');

        $mapRow = fn (Department $d) => [
            'name'           => $d->name,
            'head'           => $d->head ?? '—',
            'employee_count' => $d->employees_count,
            'status'         => ucfirst($d->status),
            'created_at'     => $d->created_at->format('Y-m-d'),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);

            $this->logDownload('hr_departments', $request);

            return $this->exportService->export(
                'Department Summary',
                ['Department', 'Head', 'Employees', 'Status', 'Created'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status'])
            );
        }

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

    /**
     * GET /api/v1/reports/hr/designations
     *
     * Designation summary report.
     */
    public function designations(Request $request)
    {
        $request->validate([
            'format'        => 'nullable|in:csv,excel,pdf',
            'status'        => 'nullable|in:active,inactive',
            'department_id' => 'nullable|uuid|exists:departments,id',
        ]);

        $query = Designation::with('department:id,name')->withCount('employees');

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);

        $query->orderBy('name');

        $mapRow = fn (Designation $d) => [
            'name'           => $d->name,
            'department'     => $d->department?->name ?? '—',
            'level'          => ucfirst($d->level ?? '—'),
            'employee_count' => $d->employees_count,
            'status'         => ucfirst($d->status),
        ];

        if ($request->filled('format')) {
            $rows = $query->get()->map($mapRow);

            $this->logDownload('hr_designations', $request);

            return $this->exportService->export(
                'Designation Summary',
                ['Designation', 'Department', 'Level', 'Employees', 'Status'],
                $rows,
                $request->format,
                $this->buildMeta($request, ['status', 'department_id'])
            );
        }

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

    // ── Helpers ──────────────────────────────────────────────

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
