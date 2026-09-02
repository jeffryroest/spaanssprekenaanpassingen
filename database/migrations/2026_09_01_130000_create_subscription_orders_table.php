<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_orders', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 160);
            $table->string('email', 254);
            $table->string('provider', 50);
            $table->string('provider_customer_ref')->nullable();
            $table->string('provider_payment_ref')->nullable();
            $table->string('payment_status', 32);
            $table->char('currency', 3);
            $table->unsignedInteger('amount_minor');
            $table->string('consent_version', 60);
            $table->timestamp('consented_at', 6);
            $table->timestamp('checkout_started_at', 6)->nullable();
            $table->timestamp('paid_at', 6)->nullable();
            $table->timestamp('last_provider_sync_at', 6)->nullable();
            $table->timestamp('completed_at', 6)->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['provider', 'provider_payment_ref']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
