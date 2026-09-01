<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('provider_event_ref')->unique();
            $table->string('event_type', 100);
            $table->json('event_payload');
            $table->timestamp('occurred_at', 6);
            $table->timestamp('received_at', 6);
            $table->timestamp('processed_at', 6)->nullable();
            $table->string('processing_status', 20)->default('received');
            $table->text('processing_error')->nullable();

            $table->index(['processing_status', 'received_at']);
            $table->index(['subscription_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
    }
};
