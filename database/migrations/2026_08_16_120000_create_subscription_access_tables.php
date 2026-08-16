<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 160);
            $table->string('billing_interval', 10);
            $table->char('currency', 3)->default('EUR');
            $table->unsignedInteger('amount_minor');
            $table->unsignedSmallInteger('trial_days')->default(7);
            $table->string('provider_price_ref')->nullable();
            $table->json('entitlements');
            $table->boolean('active')->default(true);
            $table->timestamps(precision: 6);
            $table->softDeletes('deleted_at', precision: 6);

            $table->index(['active', 'deleted_at']);
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->string('provider', 50);
            $table->string('provider_customer_ref')->nullable();
            $table->string('provider_subscription_ref')->nullable();
            $table->string('status', 20);
            $table->timestamp('trial_starts_at', 6)->nullable();
            $table->timestamp('trial_ends_at', 6)->nullable();
            $table->timestamp('current_period_starts_at', 6)->nullable();
            $table->timestamp('current_period_ends_at', 6)->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('cancelled_at', 6)->nullable();
            $table->timestamp('ended_at', 6)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['provider', 'provider_subscription_ref']);
            $table->index(['user_id', 'status', 'current_period_ends_at']);
            $table->index(['subscription_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
