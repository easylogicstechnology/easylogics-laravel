<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_founder', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('designation', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->tinyInteger('display_status')->default(1);
            $table->datetime('cdate')->nullable();
            $table->datetime('udate')->nullable();
        });

        Schema::create('site_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('location', 100)->nullable();
            $table->string('company', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('display_status')->default(1);
            $table->datetime('cdate')->nullable();
            $table->datetime('udate')->nullable();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_partners');
        Schema::dropIfExists('site_founder');
    }
};
