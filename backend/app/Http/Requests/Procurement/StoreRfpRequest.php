<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRfpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_reference' => ['nullable', 'string', 'max:255'],
            'grn_reference' => ['nullable', 'string', 'max:255'],
            // Multi-selected PR/PO/GRN reference numbers (searchable dropdowns).
            'pr_references' => ['nullable', 'array'],
            'pr_references.*' => ['string', 'max:255'],
            'po_references' => ['nullable', 'array'],
            'po_references.*' => ['string', 'max:255'],
            'grn_references' => ['nullable', 'array'],
            'grn_references.*' => ['string', 'max:255'],
            'project_code' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'uuid', 'exists:vendors,id'],
            // The supplier invoice this RFP pays. Optional for back-compat
            // with legacy RFPs created before invoices existed; new flows
            // require it via the frontend. The controller additionally
            // enforces that referenced invoices be `approved`.
            'invoice_id' => ['nullable', 'uuid', 'exists:invoices,id'],
            'payee' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:bank_transfer,cash,cheque'],
            'currency' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'department' => ['nullable', 'string', 'max:255'],
            'budget_line' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,pending,submitted,approved,paid,rejected,on_hold'],
            'payment_date' => ['nullable', 'date'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bank_details' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'signoffs' => ['nullable', 'array'],
            'signoffs.*.type' => ['required', 'string', 'max:100'],
            'signoffs.*.name' => ['nullable', 'string', 'max:255'],
            'signoffs.*.position' => ['nullable', 'string', 'max:255'],
            'signoffs.*.date' => ['nullable', 'date'],
            'signoffs.*.signature' => ['nullable', 'string'],

            'supporting_docs' => ['nullable', 'array'],
            'supporting_docs.*' => ['string'],

            // v2 §3.2 — mandatory procurement compliance gate. A payment request
            // cannot be raised without affirming procedures were followed OR
            // waived; a waiver requires a justification + a document link/reference.
            'procurement_compliance' => ['required', 'in:followed,waived'],
            'compliance_justification' => ['nullable', 'required_if:procurement_compliance,waived', 'string', 'max:2000'],
            'compliance_document_url' => ['nullable', 'required_if:procurement_compliance,waived', 'string', 'max:2000'],

            'items' => ['nullable', 'array'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.project_code' => ['nullable', 'string', 'max:255'],
            'items.*.budget_line' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.dept' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'procurement_compliance.required' => 'Confirm whether procurement procedures were followed or waived before raising this payment request.',
            'procurement_compliance.in' => 'Procurement compliance must be either "followed" or "waived".',
            'compliance_justification.required_if' => 'A justification is required when the procurement process is waived.',
            'compliance_document_url.required_if' => 'A link or reference to the justification document is required when the procurement process is waived.',
        ];
    }
}
