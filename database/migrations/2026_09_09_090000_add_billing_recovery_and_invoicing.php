<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_orders', function (Blueprint $table): void {
            $table->string('purchase_type', 20)->default('individual')->after('email');
            $table->string('company_name', 180)->nullable()->after('purchase_type');
            $table->string('vat_id', 32)->nullable()->after('company_name');
            $table->string('billing_street', 180)->nullable()->after('vat_id');
            $table->string('billing_postal_code', 32)->nullable()->after('billing_street');
            $table->string('billing_city', 120)->nullable()->after('billing_postal_code');
            $table->char('billing_country', 2)->nullable()->after('billing_city');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('past_due_since_at', 6)->nullable()->after('current_period_ends_at');
            $table->timestamp('grace_ends_at', 6)->nullable()->after('past_due_since_at');
            $table->index(['status', 'grace_ends_at']);
        });

        Schema::create('billing_invoice_sequences', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps(precision: 6);
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('subscription_event_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('invoice_year');
            $table->unsignedBigInteger('sequence_number');
            $table->string('invoice_number', 40)->unique();
            $table->json('seller_snapshot');
            $table->json('buyer_snapshot');
            $table->string('line_description', 180);
            $table->char('currency', 3);
            $table->unsignedInteger('amount_minor');
            $table->string('tax_treatment', 32);
            $table->unsignedSmallInteger('tax_rate_basis_points')->default(0);
            $table->unsignedInteger('tax_minor')->default(0);
            $table->timestamp('issued_at', 6);
            $table->timestamps(precision: 6);

            $table->unique(['invoice_year', 'sequence_number']);
            $table->index(['subscription_id', 'issued_at']);
        });

        Schema::create('billing_email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('billing_invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('kind', 40);
            $table->string('cycle_key', 64);
            $table->timestamp('due_at', 6);
            $table->timestamp('sent_at', 6)->nullable();
            $table->timestamp('cancelled_at', 6)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 80)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['subscription_id', 'kind', 'cycle_key']);
            $table->index(['sent_at', 'cancelled_at', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_email_deliveries');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_invoice_sequences');

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'grace_ends_at']);
            $table->dropColumn(['past_due_since_at', 'grace_ends_at']);
        });

        Schema::table('subscription_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'purchase_type',
                'company_name',
                'vat_id',
                'billing_street',
                'billing_postal_code',
                'billing_city',
                'billing_country',
            ]);
        });
    }
};
