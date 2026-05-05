<?php

namespace App\Services\Procurement;

use App\Models\Grn;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\Rfp;
use App\Models\Rfq;

/**
 * Walks the procurement document chain in both directions and returns a
 * compact map of related documents the frontend can render as a
 * breadcrumb trail (PR ▸ RFQ ▸ PO ▸ GRN ▸ Invoice ▸ RFP).
 *
 * Mixed link types:
 *   - Some hops use FKs (Invoice.po_id, Rfp.invoice_id) — direct lookups.
 *   - The rest are free-text reference strings (Rfq.pr_reference,
 *     PurchaseOrder.pr_reference / rfq_reference, Grn.po_reference). For
 *     those we do an exact match on the document number; if the linked
 *     doc has since been renamed or deleted, the slot returns null
 *     rather than throwing.
 *
 * Forward links (this doc → later docs) can fan out — a PR may have
 * many RFQs, a PO may have many GRNs/invoices, etc. For breadcrumbs we
 * only need to point at *one* representative, so the resolver picks the
 * most recent one. Detail pages can fetch the full list separately when
 * a fan-out view is needed.
 *
 * Returned shape (every slot is `{id, number}` or null):
 *   [
 *     'pr'      => …,
 *     'rfq'     => …,
 *     'po'      => …,
 *     'grn'     => …,
 *     'invoice' => …,
 *     'rfp'     => …,
 *   ]
 */
class DocumentChainResolver
{
    /** @var array Per-resolver cache keyed by "type:id" so repeated calls in one request don't re-query. */
    private array $cache = [];

    public function forRequisition(Requisition $pr): array
    {
        return $this->build([
            'pr_id'         => $pr->id,
            'pr_number'     => $pr->requisition_number,
        ]);
    }

    public function forRfq(Rfq $rfq): array
    {
        return $this->build([
            'rfq_id'        => $rfq->id,
            'rfq_number'    => $rfq->rfq_number,
            'pr_number'     => $rfq->pr_reference,
        ]);
    }

    public function forPurchaseOrder(PurchaseOrder $po): array
    {
        return $this->build([
            'po_id'         => $po->id,
            'po_number'     => $po->po_number,
            'rfq_number'    => $po->rfq_reference,
            'pr_number'     => $po->pr_reference,
        ]);
    }

    public function forGrn(Grn $grn): array
    {
        return $this->build([
            'grn_id'        => $grn->id,
            'grn_number'    => $grn->grn_number,
            'po_number'     => $grn->po_reference,
        ]);
    }

    public function forInvoice(Invoice $invoice): array
    {
        return $this->build([
            'invoice_id'    => $invoice->id,
            'invoice_number'=> $invoice->invoice_number,
            'po_id'         => $invoice->po_id,
        ]);
    }

    public function forRfp(Rfp $rfp): array
    {
        return $this->build([
            'rfp_id'        => $rfp->id,
            'rfp_number'    => $rfp->rfp_number,
            'invoice_id'    => $rfp->invoice_id,
            'po_number'     => $rfp->po_reference,
        ]);
    }

    /* ----------------------------------------------------------------
     * Internals
     * ---------------------------------------------------------------- */

    /**
     * Given a seed of known IDs and reference numbers, fill in every
     * slot we can. Walks both backward (parents) and forward (children).
     */
    private function build(array $seed): array
    {
        $chain = array_fill_keys(['pr', 'rfq', 'po', 'grn', 'invoice', 'rfp'], null);

        // ── Resolve known anchors ────────────────────────────────────
        if (!empty($seed['pr_id']))      $chain['pr']      = $this->slot('pr', $seed['pr_id']);
        if (!empty($seed['rfq_id']))     $chain['rfq']     = $this->slot('rfq', $seed['rfq_id']);
        if (!empty($seed['po_id']))      $chain['po']      = $this->slot('po', $seed['po_id']);
        if (!empty($seed['grn_id']))     $chain['grn']     = $this->slot('grn', $seed['grn_id']);
        if (!empty($seed['invoice_id'])) $chain['invoice'] = $this->slot('invoice', $seed['invoice_id']);
        if (!empty($seed['rfp_id']))     $chain['rfp']     = $this->slot('rfp', $seed['rfp_id']);

        // ── Resolve by reference number (backward links, string-typed) ─
        if (!$chain['pr']  && !empty($seed['pr_number']))  $chain['pr']  = $this->lookupPrByNumber($seed['pr_number']);
        if (!$chain['rfq'] && !empty($seed['rfq_number'])) $chain['rfq'] = $this->lookupRfqByNumber($seed['rfq_number']);
        if (!$chain['po']  && !empty($seed['po_number']))  $chain['po']  = $this->lookupPoByNumber($seed['po_number']);

        // ── Walk backward from the strongest anchor we have ──────────
        // Invoice has FK po_id → use it to fill PO if not already.
        if (!$chain['po'] && !empty($seed['po_id'])) {
            $chain['po'] = $this->slot('po', $seed['po_id']);
        }
        // PO carries pr_reference + rfq_reference; if we resolved PO,
        // pull those references onto the chain.
        if ($chain['po'] && (!$chain['pr'] || !$chain['rfq'])) {
            $po = PurchaseOrder::find($chain['po']['id']);
            if ($po) {
                if (!$chain['rfq'] && !empty($po->rfq_reference)) {
                    $chain['rfq'] = $this->lookupRfqByNumber($po->rfq_reference);
                }
                if (!$chain['pr'] && !empty($po->pr_reference)) {
                    $chain['pr'] = $this->lookupPrByNumber($po->pr_reference);
                }
            }
        }
        // RFQ carries pr_reference too.
        if ($chain['rfq'] && !$chain['pr']) {
            $rfq = Rfq::find($chain['rfq']['id']);
            if ($rfq && !empty($rfq->pr_reference)) {
                $chain['pr'] = $this->lookupPrByNumber($rfq->pr_reference);
            }
        }

        // ── Walk forward — pick the most-recent child at each step ──
        if ($chain['pr'] && !$chain['rfq']) {
            $rfq = Rfq::where('pr_reference', $chain['pr']['number'])->orderByDesc('created_at')->first();
            if ($rfq) $chain['rfq'] = ['id' => $rfq->id, 'number' => $rfq->rfq_number];
        }
        if ($chain['rfq'] && !$chain['po']) {
            $po = PurchaseOrder::where('rfq_reference', $chain['rfq']['number'])->orderByDesc('created_at')->first();
            if ($po) $chain['po'] = ['id' => $po->id, 'number' => $po->po_number];
        }
        if ($chain['pr'] && !$chain['po']) {
            // PO might link to PR directly when there's no RFQ in between
            $po = PurchaseOrder::where('pr_reference', $chain['pr']['number'])->orderByDesc('created_at')->first();
            if ($po) $chain['po'] = ['id' => $po->id, 'number' => $po->po_number];
        }
        if ($chain['po'] && !$chain['grn']) {
            $grn = Grn::where('po_reference', $chain['po']['number'])->orderByDesc('created_at')->first();
            if ($grn) $chain['grn'] = ['id' => $grn->id, 'number' => $grn->grn_number];
        }
        if ($chain['po'] && !$chain['invoice']) {
            // FK link — pick most recent non-cancelled
            $invoice = Invoice::where('po_id', $chain['po']['id'])
                              ->whereNotIn('status', ['cancelled', 'rejected'])
                              ->orderByDesc('created_at')->first();
            if ($invoice) $chain['invoice'] = ['id' => $invoice->id, 'number' => $invoice->invoice_number];
        }
        if ($chain['invoice'] && !$chain['rfp']) {
            $rfp = Rfp::where('invoice_id', $chain['invoice']['id'])->orderByDesc('created_at')->first();
            if ($rfp) $chain['rfp'] = ['id' => $rfp->id, 'number' => $rfp->rfp_number];
        }

        return $chain;
    }

    /** Build a {id, number} slot from a known model id without re-fetching the full row. */
    private function slot(string $type, string $id): ?array
    {
        $cacheKey = "{$type}:{$id}";
        if (array_key_exists($cacheKey, $this->cache)) return $this->cache[$cacheKey];

        $row = match ($type) {
            'pr'      => Requisition::select(['id', 'requisition_number'])->find($id),
            'rfq'     => Rfq::select(['id', 'rfq_number'])->find($id),
            'po'      => PurchaseOrder::select(['id', 'po_number'])->find($id),
            'grn'     => Grn::select(['id', 'grn_number'])->find($id),
            'invoice' => Invoice::select(['id', 'invoice_number'])->find($id),
            'rfp'     => Rfp::select(['id', 'rfp_number'])->find($id),
            default   => null,
        };

        if (!$row) {
            return $this->cache[$cacheKey] = null;
        }

        $numberCol = match ($type) {
            'pr'      => 'requisition_number',
            'rfq'     => 'rfq_number',
            'po'      => 'po_number',
            'grn'     => 'grn_number',
            'invoice' => 'invoice_number',
            'rfp'     => 'rfp_number',
        };

        return $this->cache[$cacheKey] = [
            'id'     => $row->id,
            'number' => $row->{$numberCol},
        ];
    }

    private function lookupPrByNumber(?string $number): ?array
    {
        if (empty($number)) return null;
        $row = Requisition::select(['id', 'requisition_number'])->where('requisition_number', $number)->first();
        return $row ? ['id' => $row->id, 'number' => $row->requisition_number] : null;
    }

    private function lookupRfqByNumber(?string $number): ?array
    {
        if (empty($number)) return null;
        $row = Rfq::select(['id', 'rfq_number'])->where('rfq_number', $number)->first();
        return $row ? ['id' => $row->id, 'number' => $row->rfq_number] : null;
    }

    private function lookupPoByNumber(?string $number): ?array
    {
        if (empty($number)) return null;
        $row = PurchaseOrder::select(['id', 'po_number'])->where('po_number', $number)->first();
        return $row ? ['id' => $row->id, 'number' => $row->po_number] : null;
    }
}
