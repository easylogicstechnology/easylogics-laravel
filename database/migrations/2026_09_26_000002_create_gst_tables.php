<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GST module tables, reproduced 1:1 from the existing CakePHP database (Phase 1 tables: SHOW CREATE TABLE of
 * the Neweasylogics schema; Phase 2 tables: app/Config/Schema/gst_module_phase2_tables.sql of the CakePHP project).
 *
 * The Laravel app shares the CakePHP database. Every table is only created when it is missing, never altered.
 * NOTE: the four Phase 2 tables (gst_advance_receipts, gst_advance_adjustments, gst_credit_debit_notes,
 * gst_payments) are still missing in the Neweasylogics dev database - Cake's own SQL script was never run there -
 * so the pages that read them fail identically in CakePHP until they exist.
 * Parents that must exist first: vendor_bill_details, vendor_details (member_bill_summaries is only joined, never referenced).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gst_master')) {
            Schema::create('gst_master', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('gstin', 20)->nullable();
                $table->string('legal_name', 200)->nullable();
                $table->string('trade_name', 200)->nullable();
                $table->string('registered_address', 500)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('state_code', 5)->nullable();
                $table->string('registration_type', 50)->nullable();
                $table->date('registration_date')->nullable();
                $table->enum('gst_return_frequency', ['Monthly', 'Quarterly'])->default('Monthly');
                $table->enum('default_tax_type', ['Taxable', 'Exempt', 'Nil Rated', 'Non-GST'])->default('Taxable');
                $table->string('default_place_of_supply', 100)->nullable();
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique('society_id', 'uniq_gst_master_society');
            });
        }

        if (!Schema::hasTable('gst_hsn_sac_master')) {
            Schema::create('gst_hsn_sac_master', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('code', 15);
                $table->enum('code_type', ['HSN', 'SAC'])->default('SAC');
                $table->string('description', 255);
                $table->decimal('gst_rate', 5, 2)->default(0.00);
                $table->decimal('cgst_rate', 5, 2)->default(0.00);
                $table->decimal('sgst_rate', 5, 2)->default(0.00);
                $table->decimal('igst_rate', 5, 2)->default(0.00);
                $table->decimal('cess_rate', 5, 2)->default(0.00);
                $table->enum('taxability_type', ['Taxable', 'Exempt', 'Nil Rated', 'Non-GST'])->default('Taxable');
                $table->boolean('reverse_charge_applicable')->default(0);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'code', 'effective_from'], 'uniq_gst_hsn_code');
                $table->index(['society_id', 'status'], 'idx_gst_hsn_society_status');
            });
        }

        if (!Schema::hasTable('gst_outward_supply_meta')) {
            Schema::create('gst_outward_supply_meta', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->integer('member_bill_summary_id');
                $table->integer('hsn_sac_id')->nullable();
                $table->string('place_of_supply', 100)->nullable();
                $table->enum('supply_type', ['B2B', 'B2C'])->default('B2C');
                $table->boolean('reverse_charge')->default(0);
                $table->string('remarks', 500)->nullable();
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique('member_bill_summary_id', 'uniq_gst_outward_bill');
                $table->index('society_id', 'idx_gst_outward_society');
                $table->index('hsn_sac_id', 'idx_gst_outward_hsn');
                $table->foreign('hsn_sac_id', 'fk_gst_outward_hsn')->references('id')->on('gst_hsn_sac_master');
            });
        }

        if (!Schema::hasTable('gst_itc_classification')) {
            Schema::create('gst_itc_classification', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->unsignedInteger('vendor_bill_detail_id');
                $table->enum('itc_eligibility', ['Eligible', 'Ineligible'])->default('Eligible');
                $table->string('ineligible_reason', 255)->nullable();
                $table->enum('itc_claim_status', ['Unclaimed', 'Claimed', 'Reversed'])->default('Unclaimed');
                $table->string('claim_period', 20)->nullable();
                $table->string('remarks', 500)->nullable();
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique('vendor_bill_detail_id', 'uniq_gst_itc_line');
                $table->index('society_id', 'idx_gst_itc_society');
                $table->foreign('vendor_bill_detail_id', 'fk_gst_itc_line')->references('id')->on('vendor_bill_details');
            });
        }

        if (!Schema::hasTable('gst_period_adjustments')) {
            Schema::create('gst_period_adjustments', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->enum('period_type', ['Month', 'Quarter'])->default('Month');
                $table->string('period_value', 20);
                $table->decimal('interest', 14, 2)->default(0.00);
                $table->decimal('late_fee', 14, 2)->default(0.00);
                $table->decimal('other_adjustment', 14, 2)->default(0.00);
                $table->decimal('previous_period_adjustment', 14, 2)->default(0.00);
                $table->string('remarks', 500)->nullable();
                $table->integer('created_by');
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'financial_year_id', 'period_type', 'period_value'], 'uniq_gst_period_adj');
            });
        }

        if (!Schema::hasTable('gst_audit_logs')) {
            Schema::create('gst_audit_logs', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('ref_table', 50);
                $table->integer('ref_id');
                $table->string('action', 30);
                $table->text('old_data')->nullable();
                $table->text('new_data')->nullable();
                $table->integer('changed_by');
                $table->dateTime('changed_at');

                $table->index(['ref_table', 'ref_id'], 'idx_gst_audit_ref');
                $table->index('society_id', 'idx_gst_audit_society');
            });
        }

        if (!Schema::hasTable('gst_advance_receipts')) {
            Schema::create('gst_advance_receipts', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->integer('receipt_no');
                $table->date('receipt_date');
                $table->enum('party_type', ['Member', 'Vendor'])->default('Member');
                $table->integer('member_id')->nullable();
                $table->unsignedInteger('vendor_detail_id')->nullable();
                $table->string('party_name', 200)->nullable();
                $table->string('gstin', 20)->nullable();
                $table->string('pan_no', 10)->nullable();
                $table->decimal('amount_received', 14, 2)->default(0.00);
                $table->decimal('taxable_value', 14, 2)->default(0.00);
                $table->decimal('gst_rate', 5, 2)->default(0.00);
                $table->decimal('cgst_amount', 14, 2)->default(0.00);
                $table->decimal('sgst_amount', 14, 2)->default(0.00);
                $table->decimal('igst_amount', 14, 2)->default(0.00);
                $table->decimal('total_gst', 14, 2)->default(0.00);
                $table->enum('adjustment_status', ['Unadjusted', 'Partially Adjusted', 'Fully Adjusted'])->default('Unadjusted');
                $table->string('remarks', 500)->nullable();
                $table->integer('created_by');
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'financial_year_id', 'receipt_no'], 'uniq_gst_advance_no');
                $table->index(['society_id', 'financial_year_id'], 'idx_gst_advance_society_fy');
            });
        }

        if (!Schema::hasTable('gst_advance_adjustments')) {
            Schema::create('gst_advance_adjustments', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->integer('gst_advance_receipt_id');
                $table->enum('adjusted_against_type', ['MemberBill', 'VendorBill'])->default('MemberBill');
                $table->integer('adjusted_against_id');
                $table->string('adjusted_against_invoice_no', 50)->nullable();
                $table->date('adjustment_date');
                $table->decimal('adjusted_amount', 14, 2)->default(0.00);
                $table->decimal('adjusted_taxable_value', 14, 2)->default(0.00);
                $table->decimal('adjusted_gst_amount', 14, 2)->default(0.00);
                $table->string('remarks', 500)->nullable();
                $table->integer('created_by');
                $table->dateTime('cdate');

                $table->index('gst_advance_receipt_id', 'idx_gst_adv_adj_receipt');
                $table->index('society_id', 'idx_gst_adv_adj_society');
                $table->foreign('gst_advance_receipt_id', 'fk_gst_adv_adj_receipt')->references('id')->on('gst_advance_receipts');
            });
        }

        if (!Schema::hasTable('gst_credit_debit_notes')) {
            Schema::create('gst_credit_debit_notes', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->enum('note_type', ['Credit Note', 'Debit Note'])->default('Credit Note');
                $table->integer('note_no');
                $table->date('note_date');
                $table->enum('original_invoice_type', ['MemberBill', 'VendorBill'])->default('MemberBill');
                $table->integer('original_invoice_id');
                $table->string('original_invoice_no', 50)->nullable();
                $table->date('original_invoice_date')->nullable();
                $table->enum('party_type', ['Member', 'Vendor'])->default('Member');
                $table->integer('member_id')->nullable();
                $table->unsignedInteger('vendor_detail_id')->nullable();
                $table->string('party_name', 200)->nullable();
                $table->string('gstin', 20)->nullable();
                $table->integer('hsn_sac_id')->nullable();
                $table->decimal('taxable_amount', 14, 2)->default(0.00);
                $table->decimal('cgst_amount', 14, 2)->default(0.00);
                $table->decimal('sgst_amount', 14, 2)->default(0.00);
                $table->decimal('igst_amount', 14, 2)->default(0.00);
                $table->decimal('total_gst', 14, 2)->default(0.00);
                $table->decimal('total_amount', 14, 2)->default(0.00);
                $table->string('reason', 255)->nullable();
                $table->string('remarks', 500)->nullable();
                $table->boolean('status')->default(1);
                $table->integer('created_by');
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'financial_year_id', 'note_type', 'note_no'], 'uniq_gst_note_no');
                $table->index(['society_id', 'financial_year_id'], 'idx_gst_note_society_fy');
                $table->index('hsn_sac_id', 'idx_gst_note_hsn');
                $table->foreign('hsn_sac_id', 'fk_gst_note_hsn')->references('id')->on('gst_hsn_sac_master');
            });
        }

        if (!Schema::hasTable('gst_payments')) {
            Schema::create('gst_payments', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->string('gstin', 20)->nullable();
                $table->enum('period_type', ['Month', 'Quarter'])->default('Month');
                $table->string('period_value', 20);
                $table->decimal('cgst', 14, 2)->default(0.00);
                $table->decimal('sgst', 14, 2)->default(0.00);
                $table->decimal('igst', 14, 2)->default(0.00);
                $table->decimal('cess', 14, 2)->default(0.00);
                $table->decimal('interest', 14, 2)->default(0.00);
                $table->decimal('late_fee', 14, 2)->default(0.00);
                $table->decimal('other_amount', 14, 2)->default(0.00);
                $table->decimal('total_payable', 14, 2)->default(0.00);
                $table->string('challan_number', 30)->nullable();
                $table->string('cin_cpin', 30)->nullable();
                $table->string('bank_portal_reference', 100)->nullable();
                $table->date('payment_date')->nullable();
                $table->decimal('amount_paid', 14, 2)->nullable();
                $table->enum('payment_status', ['Draft', 'Prepared', 'Paid'])->default('Draft');
                $table->integer('prepared_by');
                $table->dateTime('prepared_at');
                $table->string('remarks', 500)->nullable();
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->index(['society_id', 'financial_year_id', 'period_type', 'period_value'], 'idx_gst_payment_group');
                $table->index('payment_status', 'idx_gst_payment_status');
            });
        }

        if (!Schema::hasTable('gst_reconciliation_entries')) {
            Schema::create('gst_reconciliation_entries', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->enum('period_type', ['Month', 'Quarter'])->default('Month');
                $table->string('period_value', 20);
                $table->decimal('return_taxable_value', 14, 2)->default(0.00);
                $table->decimal('return_cgst', 14, 2)->default(0.00);
                $table->decimal('return_sgst', 14, 2)->default(0.00);
                $table->decimal('return_igst', 14, 2)->default(0.00);
                $table->integer('return_invoice_count')->default(0);
                $table->decimal('books_taxable_value', 14, 2)->default(0.00);
                $table->decimal('books_cgst', 14, 2)->default(0.00);
                $table->decimal('books_sgst', 14, 2)->default(0.00);
                $table->decimal('books_igst', 14, 2)->default(0.00);
                $table->integer('books_invoice_count')->default(0);
                $table->enum('status', ['Matched', 'Mismatch', 'Needs Review'])->default('Needs Review');
                $table->string('remarks', 500)->nullable();
                $table->integer('created_by');
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'financial_year_id', 'period_type', 'period_value'], 'uniq_gst_recon_period');
            });
        }

        if (!Schema::hasTable('gst_return_status')) {
            Schema::create('gst_return_status', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->enum('period_type', ['Month', 'Quarter'])->default('Month');
                $table->string('period_value', 20);
                $table->enum('status', ['Draft', 'Ready for Review', 'Reconciled', 'Ready to File', 'Filed', 'Amended'])->default('Draft');
                $table->integer('updated_by');
                $table->dateTime('updated_at');
                $table->dateTime('cdate');

                $table->unique(['society_id', 'financial_year_id', 'period_type', 'period_value'], 'uniq_gst_return_status_period');
            });
        }
    }

    public function down(): void
    {
        // Deliberately empty: the tables belong to the shared CakePHP database and must never be dropped from here.
    }
};
