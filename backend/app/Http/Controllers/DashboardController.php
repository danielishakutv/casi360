<?php

namespace App\Http\Controllers;

use App\Models\Boq;
use App\Models\Employee;
use App\Models\Notice;
use App\Models\Project;
use App\Models\Requisition;
use App\Services\Access\DepartmentScope;
use App\Services\Procurement\DocumentScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Landing-page dashboard summary.
 *
 * The payload is shaped by what the viewer is allowed to see:
 *
 *   - scope = "org"        → admins, the Country Director, and Operations
 *                            managers/leads (DepartmentScope::canSeeAllDepartments).
 *                            Returns organisation-wide figures: total staff,
 *                            active projects, total budget, notices, plus recent
 *                            staff / notices / active projects.
 *
 *   - scope = "department" → everyone else. Returns ONLY things that concern the
 *                            user: their own Purchase Requests and BOQs (scoped
 *                            via DocumentScopeService — the same "concerns me +
 *                            department-mates" rule the document lists use), their
 *                            department's projects, and notices targeted at them.
 *                            No cross-department finance/procurement/HR analytics.
 *
 * This dedicated endpoint intentionally does NOT reuse the module stat endpoints
 * (/hr/employees/stats, /projects/stats, …) so adding dashboard scoping here
 * carries zero regression risk for those module pages.
 */
class DashboardController extends Controller
{
    public function __construct(
        private DepartmentScope $scope,
        private DocumentScopeService $docScope,
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = $this->scope->canSeeAllDepartments($user)
            ? $this->orgSummary()
            : $this->departmentSummary($user);

        return $this->success(array_merge($payload, [
            // Single source of truth for the "as of" stamp the dashboard shows,
            // so date + time are always visible (transparency / accountability).
            'generated_at' => now()->toISOString(),
        ]));
    }

    /* ---------------------------------------------------------------- */
    /* Organisation-wide (privileged) view                              */
    /* ---------------------------------------------------------------- */

    private function orgSummary(): array
    {
        return [
            'scope'      => 'org',
            'department' => null,
            'stats'      => [
                'total_staff'     => Employee::where('status', '!=', 'terminated')->count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'total_budget'    => (float) Project::sum('total_budget'),
                'notices'         => Notice::published()->count(),
            ],
            'recent_staff'    => Employee::with(['department', 'user:id,department'])
                ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values(),
            'recent_notices'  => Notice::published()
                ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values(),
            'active_projects' => Project::with('department')->where('status', 'active')
                ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values(),
        ];
    }

    /* ---------------------------------------------------------------- */
    /* Department-scoped (own data only) view                           */
    /* ---------------------------------------------------------------- */

    private function departmentSummary($user): array
    {
        // Purchase Requests that concern this user.
        $reqBase = Requisition::query();
        $this->docScope->applyToRequisitions($reqBase, $user);
        $openPrCount = (clone $reqBase)
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])->count();
        $myReqs = (clone $reqBase)
            ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values();

        // BOQs that concern this user.
        $boqBase = Boq::query();
        $this->docScope->applyToBoqs($boqBase, $user);
        $boqCount = (clone $boqBase)->count();
        $myBoqs = (clone $boqBase)
            ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values();

        // The user's department's projects.
        $deptId = $this->scope->userDepartmentId($user);
        $projBase = Project::with('department')
            ->whereNotIn('status', ['closed']);
        if ($deptId) {
            $projBase->where('department_id', $deptId);
        } else {
            $projBase->whereRaw('1 = 0'); // no resolvable department → nothing
        }
        $deptProjectCount = (clone $projBase)->count();
        $deptProjects = (clone $projBase)
            ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values();

        // Notices targeted at this user.
        $noticeBase = Notice::published()->visibleTo($user);
        $noticeCount = (clone $noticeBase)->count();
        $myNotices = (clone $noticeBase)
            ->orderByDesc('created_at')->take(5)->get()->map->toApiArray()->values();

        return [
            'scope'      => 'department',
            'department' => $user->department,
            'stats'      => [
                'my_open_prs'         => $openPrCount,
                'my_boqs'             => $boqCount,
                'department_projects' => $deptProjectCount,
                'notices'             => $noticeCount,
            ],
            'my_requisitions'     => $myReqs,
            'my_boqs'             => $myBoqs,
            'department_projects' => $deptProjects,
            'recent_notices'      => $myNotices,
        ];
    }
}
