<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp bill sending: whatsapp_bill_logs (one row per bill per send) and whatsapp_bill_batches (one row per
 * "Send WhatsApp Bill" click). Same tables as the CakePHP app (app/Config/Schema/whatsapp_tables.sql), so both
 * applications read and write one history.
 *
 * Only creates a table that is missing; nothing is dropped or changed, so it is safe on a database where the
 * CakePHP SQL was already run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_bill_logs')) {
            Schema::create('whatsapp_bill_logs', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->charset = 'utf8';
                $t->collation = 'utf8_general_ci';
                $t->unsignedBigInteger('id', true);
                $t->string('batch_id', 50);
                $t->integer('society_id');
                $t->integer('member_id');
                $t->integer('bill_summary_id');
                $t->integer('bill_no')->nullable();
                $t->string('bill_month', 10)->nullable();
                $t->smallInteger('financial_year_id')->nullable();
                $t->string('flat_no', 100)->nullable();
                $t->string('member_name', 200)->nullable();
                $t->string('phone', 20)->nullable();
                $t->float('amount', 15, 2)->nullable();
                $t->enum('status', ['PENDING', 'SENT', 'DELIVERED', 'READ', 'FAILED', 'API_LIMIT_REACHED', 'INVALID_PHONE', 'PDF_FAILED'])->default('PENDING');
                $t->text('api_response')->nullable();
                $t->string('api_message_id', 255)->nullable();
                $t->text('error_message')->nullable();
                $t->unsignedTinyInteger('retry_count')->default(0);
                $t->string('pdf_path', 500)->nullable();
                $t->dateTime('sent_at')->nullable();
                $t->dateTime('delivered_at')->nullable();
                $t->dateTime('read_at')->nullable();
                $t->dateTime('created_at');
                $t->dateTime('updated_at')->nullable();

                $t->index('batch_id', 'idx_batch');
                $t->index('society_id', 'idx_society');
                $t->index('member_id', 'idx_member');
                $t->index('bill_summary_id', 'idx_bill');
                $t->index('status', 'idx_status');
                $t->index(['society_id', 'batch_id'], 'idx_society_batch');
                $t->index('api_message_id', 'idx_api_message_id');
            });
        }

        if (!Schema::hasTable('whatsapp_bill_batches')) {
            Schema::create('whatsapp_bill_batches', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->charset = 'utf8';
                $t->collation = 'utf8_general_ci';
                $t->unsignedBigInteger('id', true);
                $t->string('batch_id', 50)->unique();
                $t->integer('society_id');
                $t->string('bill_month', 10)->nullable();
                $t->smallInteger('financial_year_id')->nullable();
                $t->integer('total_bills')->default(0);
                $t->integer('sent_count')->default(0);
                $t->integer('delivered_count')->default(0);
                $t->integer('read_count')->default(0);
                $t->integer('failed_count')->default(0);
                $t->integer('pending_count')->default(0);
                $t->integer('limit_count')->default(0);
                $t->integer('invalid_phone_count')->default(0);
                $t->integer('pdf_failed_count')->default(0);
                $t->enum('status', ['IN_PROGRESS', 'COMPLETED', 'PARTIAL', 'STOPPED'])->default('IN_PROGRESS');
                $t->string('stopped_at_bill', 100)->nullable();
                $t->integer('created_by')->nullable();
                $t->dateTime('created_at');
                $t->dateTime('completed_at')->nullable();

                $t->index('society_id', 'idx_society');
                $t->index('status', 'idx_status');
            });
        }
    }

    /** Never drops: the tables hold the send history the CakePHP app shares. */
    public function down(): void
    {
    }
};
