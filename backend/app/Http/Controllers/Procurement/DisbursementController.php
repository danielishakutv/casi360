<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreDisbursementRequest;
use App\Models\AuditLog;
use App\Models\Disbursement;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisbursementController extends Controller
{
    /**
     * GET /api/v1/procurement/purchase-orders/{id}/disbursements
     */
    public function index(string $purchaseOrderId): JsonResponse
    {
        $order = PurchaseOrder::findOrFail($purchaseOrderId);

        $disbursements = $order->disbursements()
            ->with('disbursedBy')
            ->orderBy('payment_date', 'desc')
            ->get();

        return $this->success([
            'disbursements' => $disbursements->map->toApiArray(),
            'purchase_order_id' => $order->id,
            'po_number' => $order->po_number,
            'total_amount' => (float) $order->total_amount,
            'total_disbursed' => (float) $disbursements->sum('amount'),
            'balance' => (float) $order->total_amount - (float) $disbursements->sum('amount'),
        ]);
    }

    /**
     * POST /api/v1/procurement/purchase-orders/{id}/disbursements
     */
    public function store(StoreDisbursementRequest $request, string $purchaseOrderId): JsonResponse
    {
        $order = PurchaseOrder::findOrFail($purchaseOrderId);

        if ($order->status !== 'approved' && $order->status !== 'disbursed') {
            return $this->error('Disbursements can only be made for approved purchase orders.', 422);
        }

        $data = $request->validated();

        // Check total disbursed won't exceed PO amount
        $totalDisbursed = $order->disbursements()->sum('amount');
        $remaining = (float) $order->total_amount - (float) $totalDisbursed;

        if ((float) $data['amount'] > $remaining) {
            return $this->error(
                "Disbursement amount exceeds remaining balance. Remaining: {$remaining}",
                422
            );
        }

        return DB::transaction(function () use ($data, $order) {
            $data['purchase_order_id'] = $order->id;
            $data['disbursed_by'] = auth()->id();

            $disbursement = Disbursement::create($data);

            // Update PO payment status
            $totalDisbursed = $order->disbursements()->sum('amount');
            if ($totalDisbursed >= (float) $order->total_amount) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'disbursed',
                ]);
            } else {
                $order->update(['payment_status' => 'partially_paid']);
            }

            AuditLog::record(
                auth()->id(),
                'disbursement_created',
                'disbursement',
                $disbursement->id,
                null,
                $disbursement->toApiArray()
            );

            return $this->success([
                'disbursement' => $disbursement->load('disbursedBy')->toApiArray(),
            ], 'Disbursement recorded successfully', 201);
        });
    }
}
