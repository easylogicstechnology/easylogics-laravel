<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One WhatsApp sender per reseller (Admin > WhatsApp APIs): the reseller's own number and API account. Bills of
 * a society go out through its reseller's sender; a society whose reseller has none uses the common API (.env).
 *
 * The table has the CakePHP columns (reseller_whatsapp_senders) and gets the extra columns the provider choice
 * needs. Only ADDS what is missing; nothing is dropped or changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reseller_whatsapp_senders')) {
            Schema::create('reseller_whatsapp_senders', function (Blueprint $t) {
                $t->engine = 'InnoDB';
                $t->charset = 'utf8';
                $t->collation = 'utf8_general_ci';
                $t->increments('id');
                $t->integer('reseller_id')->unique();
                $t->string('sender_phone', 20);
                $t->string('provider', 20)->default('interakt');
                $t->text('api_key_enc');
                $t->string('api_key_last4', 4)->nullable();
                $t->text('webhook_secret_enc')->nullable();
                $t->enum('status', ['PENDING', 'ACTIVE', 'DISCONNECTED', 'DISABLED'])->default('PENDING')->index();
                $t->string('last_error', 255)->nullable();
                $t->integer('created_by')->nullable();
                $t->dateTime('created_at');
                $t->dateTime('updated_at')->nullable();
            });
        }

        $extra = [
            'api_url' => fn (Blueprint $t) => $t->string('api_url', 255)->nullable(),
            'phone_number_id' => fn (Blueprint $t) => $t->string('phone_number_id', 64)->nullable(),
            'auth_header' => fn (Blueprint $t) => $t->string('auth_header', 50)->nullable(),
            'template_name' => fn (Blueprint $t) => $t->string('template_name', 100)->nullable(),
            'body_params' => fn (Blueprint $t) => $t->string('body_params', 255)->nullable(),
            'language_code' => fn (Blueprint $t) => $t->string('language_code', 10)->nullable(),
        ];
        foreach ($extra as $col => $add) {
            if (!Schema::hasColumn('reseller_whatsapp_senders', $col)) {
                Schema::table('reseller_whatsapp_senders', fn (Blueprint $t) => $add($t));
            }
        }
    }

    public function down(): void
    {
    }
};
