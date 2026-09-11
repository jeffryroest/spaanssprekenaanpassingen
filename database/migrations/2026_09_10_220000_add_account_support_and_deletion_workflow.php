<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('privacy_erased_at', 6)->nullable()->after('email_verified_at')->index();
        });

        Schema::table('content_role_audits', function (Blueprint $table): void {
            $table->string('to_role', 32)->nullable()->change();
        });

        Schema::create('account_support_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 24);
            $table->string('summary', 500);
            $table->timestamp('follow_up_at', 6)->nullable();
            $table->timestamp('resolved_at', 6)->nullable();
            $table->timestamps(precision: 6);

            $table->index(['user_id', 'resolved_at', 'created_at'], 'support_notes_account_status_index');
            $table->index(['resolved_at', 'follow_up_at'], 'support_notes_follow_up_index');
        });

        Schema::create('account_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24);
            $table->timestamp('requested_at', 6);
            $table->timestamp('processed_at', 6)->nullable();
            $table->date('billing_retained_until')->nullable();
            $table->timestamps(precision: 6);

            $table->index(['user_id', 'status'], 'account_deletion_user_status_index');
            $table->index(['status', 'requested_at'], 'account_deletion_queue_index');
            $table->index('billing_retained_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('account_support_notes');

        Schema::table('content_role_audits', function (Blueprint $table): void {
            $table->string('to_role', 32)->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['privacy_erased_at']);
            $table->dropColumn('privacy_erased_at');
        });
    }
};
