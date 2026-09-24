<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_operations', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 100)->unique();
            $table->string('username', 50);
            $table->string('currency', 10);
            $table->bigInteger('delta');
            $table->bigInteger('before_balance');
            $table->bigInteger('after_balance');
            $table->string('operator', 120);
            $table->string('ip', 64)->nullable();
            $table->string('reason', 255);
            $table->string('status', 20)->default('completed');
            $table->timestamps();
            $table->index(['username', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_operations');
    }
};
