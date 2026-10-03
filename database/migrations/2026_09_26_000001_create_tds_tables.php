<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TDS module tables, reproduced 1:1 from the existing CakePHP database (SHOW CREATE TABLE of the
 * production schema): same columns, types, NULL/DEFAULT, indexes, unique keys and foreign keys.
 *
 * The Laravel app shares the CakePHP database, where these tables already exist - every
 * table is therefore only created when it is missing (fresh installs), never altered.
 * Parents that must exist first: society_ledger_heads, vendor_details, vendor_bills, society_payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tds_sections')) {
            Schema::create('tds_sections', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('section_code', 15);
                $table->string('nature_of_payment', 255);
                $table->text('description')->nullable();
                $table->enum('deductee_type', ['Individual_HUF', 'Company', 'Firm', 'All'])->default('All');
                $table->enum('resident_type', ['Resident', 'Non-Resident', 'Both'])->default('Resident');
                $table->decimal('rate_percent', 5, 2)->default(0.00);
                $table->decimal('threshold_limit', 14, 2)->default(0.00);
                $table->enum('threshold_basis', ['Single Transaction', 'Aggregate in FY'])->default('Aggregate in FY');
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->integer('tds_payable_ledger_head_id')->nullable();
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_id', 'section_code', 'deductee_type', 'resident_type', 'effective_from'], 'uniq_tds_section');
                $table->index(['society_id', 'status'], 'idx_tds_sections_society_status');
                $table->index('tds_payable_ledger_head_id', 'idx_tds_sections_ledger_head');
                $table->foreign('tds_payable_ledger_head_id', 'fk_tds_sections_ledger_head')->references('id')->on('society_ledger_heads');
            });
        }

        if (!Schema::hasTable('tds_deductees')) {
            Schema::create('tds_deductees', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->unsignedInteger('vendor_detail_id');
                $table->enum('deductee_type', ['Individual_HUF', 'Company', 'Firm', 'Others'])->default('Individual_HUF');
                $table->enum('resident_status', ['Resident', 'Non-Resident'])->default('Resident');
                $table->string('lower_deduction_cert_no', 50)->nullable();
                $table->decimal('lower_deduction_rate', 5, 2)->nullable();
                $table->date('lower_deduction_valid_from')->nullable();
                $table->date('lower_deduction_valid_upto')->nullable();
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique('vendor_detail_id', 'uniq_tds_deductee_vendor');
                $table->index('society_id', 'idx_tds_deductees_society');
                $table->foreign('vendor_detail_id', 'fk_tds_deductees_vendor')->references('id')->on('vendor_details');
            });
        }

        if (!Schema::hasTable('tds_transactions')) {
            Schema::create('tds_transactions', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->smallInteger('financial_year_id');
                $table->integer('tds_section_id');
                $table->unsignedInteger('vendor_detail_id');
                $table->unsignedInteger('vendor_bill_id')->nullable();
                $table->integer('society_payment_id')->nullable();
                $table->string('pan_no', 10)->nullable();
                $table->date('deduction_date');
                $table->date('payment_date')->nullable();
                $table->string('invoice_no', 50)->nullable();
                $table->decimal('gross_amount', 14, 2)->default(0.00);
                $table->decimal('tds_rate', 5, 2)->default(0.00);
                $table->decimal('tds_amount', 14, 2)->default(0.00);
                $table->decimal('net_amount', 14, 2)->default(0.00);
                $table->integer('tds_payable_ledger_head_id')->nullable();
                $table->integer('tds_challan_id')->nullable();
                $table->enum('challan_status', ['Pending', 'Challan Prepared', 'Payment Pending', 'Paid', 'Challan Verified', 'Filed'])->default('Pending');
                $table->string('remarks', 500)->nullable();
                $table->boolean('is_reversed')->default(0);
                $table->integer('reversed_by')->nullable();
                $table->dateTime('reversed_at')->nullable();
                $table->string('reversal_reason', 255)->nullable();
                $table->integer('created_by');
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->unique(['society_payment_id', 'tds_section_id', 'vendor_detail_id'], 'uniq_tds_txn_payment');
                $table->index(['society_id', 'financial_year_id'], 'idx_tds_txn_society_fy');
                $table->index('vendor_detail_id', 'idx_tds_txn_vendor');
                $table->index('tds_challan_id', 'idx_tds_txn_challan');
                $table->index('vendor_bill_id', 'idx_tds_txn_bill');
                $table->index('tds_section_id', 'idx_tds_txn_section');
                $table->foreign('vendor_bill_id', 'fk_tds_txn_bill')->references('id')->on('vendor_bills');
                $table->foreign('tds_payable_ledger_head_id', 'fk_tds_txn_ledger_head')->references('id')->on('society_ledger_heads');
                $table->foreign('society_payment_id', 'fk_tds_txn_payment')->references('id')->on('society_payments');
                $table->foreign('tds_section_id', 'fk_tds_txn_section')->references('id')->on('tds_sections');
                $table->foreign('vendor_detail_id', 'fk_tds_txn_vendor')->references('id')->on('vendor_details');
            });
        }

        if (!Schema::hasTable('tds_challans')) {
            Schema::create('tds_challans', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('tan_no', 10);
                $table->smallInteger('financial_year_id');
                $table->string('assessment_year', 9);
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
                $table->tinyInteger('month');
                $table->string('major_head', 20)->nullable();
                $table->string('minor_head', 20)->nullable();
                $table->decimal('total_tds_amount', 14, 2)->default(0.00);
                $table->integer('deductee_count')->default(0);
                $table->string('challan_number', 20)->nullable();
                $table->string('bsr_code', 10)->nullable();
                $table->date('challan_date')->nullable();
                $table->string('cin', 30)->nullable();
                $table->enum('payment_status', ['Pending', 'Challan Prepared', 'Payment Pending', 'Paid', 'Challan Verified', 'Filed'])->default('Pending');
                $table->integer('prepared_by');
                $table->dateTime('prepared_at');
                $table->integer('verified_by')->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->string('remarks', 500)->nullable();
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');
                $table->dateTime('udate');

                $table->index(['society_id', 'tan_no', 'financial_year_id', 'month'], 'idx_tds_challan_group');
                $table->index('payment_status', 'idx_tds_challans_status');
            });
        }

        if (!Schema::hasTable('tds_challan_transactions')) {
            Schema::create('tds_challan_transactions', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('tds_challan_id');
                $table->integer('tds_transaction_id');
                $table->dateTime('cdate');

                $table->unique('tds_transaction_id', 'uniq_tds_challan_txn');
                $table->index('tds_challan_id', 'idx_tds_challan_txn_challan');
                $table->foreign('tds_challan_id', 'fk_tds_challan_txn_challan')->references('id')->on('tds_challans');
                $table->foreign('tds_transaction_id', 'fk_tds_challan_txn_txn')->references('id')->on('tds_transactions');
            });
        }

        if (!Schema::hasTable('tds_certificates')) {
            Schema::create('tds_certificates', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->unsignedInteger('vendor_detail_id');
                $table->smallInteger('financial_year_id');
                $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4', 'Full Year'])->default('Full Year');
                $table->string('certificate_number', 30)->nullable();
                $table->decimal('total_tds_amount', 14, 2)->default(0.00);
                $table->integer('generated_by');
                $table->dateTime('generated_at');
                $table->boolean('status')->default(1);
                $table->dateTime('cdate');

                $table->index(['society_id', 'financial_year_id'], 'idx_tds_cert_society_fy');
                $table->index('vendor_detail_id', 'idx_tds_cert_vendor');
                $table->foreign('vendor_detail_id', 'fk_tds_cert_vendor')->references('id')->on('vendor_details');
            });
        }

        if (!Schema::hasTable('tds_audit_logs')) {
            Schema::create('tds_audit_logs', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('society_id');
                $table->string('ref_table', 50);
                $table->integer('ref_id');
                $table->string('action', 30);
                $table->text('old_data')->nullable();
                $table->text('new_data')->nullable();
                $table->integer('changed_by');
                $table->dateTime('changed_at');

                $table->index(['ref_table', 'ref_id'], 'idx_tds_audit_ref');
                $table->index('society_id', 'idx_tds_audit_society');
            });
        }
    }

    public function down(): void
    {
        // Deliberately empty: the tables belong to the shared CakePHP database and must never be dropped from here.
    }
};
