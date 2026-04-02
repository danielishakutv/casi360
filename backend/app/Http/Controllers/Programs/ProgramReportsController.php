<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramReportsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        // Beneficiaries per project
        $beneficiariesByProject = Beneficiary::select('project_id', DB::raw('count(*) as count'))
            ->groupBy('project_id')
            ->with('project:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'project_id' => $row->project_id,
                    'project_name' => $row->project?->name,
                    'beneficiary_count' => $row->count,
                ];
            });

        // Beneficiaries by status
        $beneficiariesByStatus = Beneficiary::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Enrollment trends (last 12 months)
        $enrollmentTrends = Beneficiary::select(
                DB::raw("DATE_FORMAT(enrollment_date, '%Y-%m') as month"),
                DB::raw('count(*) as count')
            )
            ->where('enrollment_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Gender distribution
        $genderDistribution = Beneficiary::select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('count', 'gender');

        return $this->success([
            'total_beneficiaries' => Beneficiary::count(),
            'active_beneficiaries' => Beneficiary::where('status', 'active')->count(),
            'total_projects' => Project::count(),
            'beneficiaries_by_project' => $beneficiariesByProject,
            'beneficiaries_by_status' => $beneficiariesByStatus,
            'enrollment_trends' => $enrollmentTrends,
            'gender_distribution' => $genderDistribution,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'format' => ['nullable', 'in:json,csv'],
        ]);

        $query = Beneficiary::with('project:id,name');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $data = $query->get()->map->toApiArray();

        return $this->success([
            'beneficiaries' => $data,
            'total' => $data->count(),
            'exported_at' => now()->toIso8601String(),
        ], 'Report exported successfully');
    }
}
