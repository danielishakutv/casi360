<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor invoices — the document the supplier sends after delivery, sitting
 * between PO/GRN and the RFP that pays for it.
 *
 * MVP scope: amount-only (no file attachment yet). The supplier's own
 * invoice number is captured as a free-text reference. Many invoices may
 * be raised against a single PO (installments / partial deliveries), and
 * each invoice gates the RFP that pays it.
 *
 * Workflow: pending → approved | rejected. Procurement creates and edits
 * while pending; Finance approves or rejects. Once paid, status flips to
 * paid via the RFP payment flow (handled in a later phase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Supplier's own reference (e.g. "INV-2026-001"). Free text so
            // any vendor's numbering scheme works. Not enforced unique —
            // numbering systems reset, and uniqueness is the business's
            // problem to solve via search rather than the DB's job.
            $table->string('invoice_number', 100);

            // Document linkage
            $table->foreignUuid('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            // Money
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('NGN');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->text('notes')->nullable();

            // Status machine
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'cancelled'])
                  ->default('pending');

            // Audit / who-did-what
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->timestamps();

            // Indexes for the common access patterns
            $table->index('po_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index(['po_id', 'status']);
            $table->index('created_by');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
