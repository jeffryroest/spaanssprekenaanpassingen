<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->char('request_id', 36)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent_family', 120)->nullable();
            $table->dateTime('created_at', precision: 6);

            $table->index(['subject_type', 'subject_id', 'created_at'], 'audit_logs_subject_index');
            $table->index(['actor_user_id', 'created_at'], 'audit_logs_actor_index');
            $table->index('request_id', 'audit_logs_request_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
