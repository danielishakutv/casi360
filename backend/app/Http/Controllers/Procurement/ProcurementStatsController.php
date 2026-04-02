<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Boq;
use App\Models\Grn;
use App\Models\Rfp;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProcurementStatsController extends Controller
{
    public function index(): JsonResponse
    {
        $boqStats = Boq::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $rfqStats = Rfq::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $grnStats = Grn::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $rfpStats = Rfp::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return $this->success([
            'boq' => [
                'total' => $boqStats->sum(),
                'by_status' => $boqStats,
            ],
            'rfq' => [
                'total' => $rfqStats->sum(),
                'by_status' => $rfqStats,
            ],
            'grn' => [
                'total' => $grnStats->sum(),
                'by_status' => $grnStats,
            ],
            'rfp' => [
                'total' => $rfpStats->sum(),
                'by_status' => $rfpStats,
                'total_value' => (float) Rfp::sum('total_amount'),
                'total_paid' => (float) Rfp::where('status', 'paid')->sum('total_amount'),
            ],
        ]);
    }
}
