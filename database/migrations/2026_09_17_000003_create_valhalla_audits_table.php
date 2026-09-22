<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valhalla_audits', function (Blueprint $table) {
            $table->id();
            $table->string('operator');
            $table->string('ip', 45)->nullable();
            $table->string('action');
            $table->string('resource');
            $table->string('target_key')->nullable();
            $table->text('value_before')->nullable();
            $table->text('value_after')->nullable();
            $table->string('note')->nullable();
            $table->string('result')->default('ok');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valhalla_audits');
    }
};
