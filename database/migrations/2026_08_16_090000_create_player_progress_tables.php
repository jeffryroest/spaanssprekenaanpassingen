<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_states', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->unsignedInteger('confianza')->default(0);
            $table->unsignedInteger('valentia')->default(0);
            $table->unsignedInteger('state_version')->default(1);
            $table->date('last_learning_date')->nullable();
            $table->timestamps(precision: 6);
        });

        Schema::create('user_mission_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mission_key', 120);
            $table->foreignId('source_content_node_id')->nullable()->constrained('content_nodes')->nullOnDelete();
            $table->unsignedInteger('source_content_version');
            $table->string('status', 30)->default('completed');
            $table->unsignedInteger('completion_count')->default(0);
            $table->unsignedInteger('best_xp')->default(0);
            $table->unsignedInteger('best_spoken_turns')->default(0);
            $table->boolean('spoken_goal_completed')->default(false);
            $table->json('state_snapshot')->nullable();
            $table->timestamp('first_completed_at', 6)->nullable();
            $table->timestamp('last_completed_at', 6)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['user_id', 'mission_key']);
            $table->index(['user_id', 'status']);
            $table->index('source_content_node_id');
        });

        Schema::create('mission_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mission_key', 120);
            $table->foreignId('source_content_node_id')->nullable()->constrained('content_nodes')->nullOnDelete();
            $table->unsignedInteger('source_content_version');
            $table->unsignedInteger('attempt_number');
            $table->uuid('completion_key');
            $table->string('status', 30)->default('completed');
            $table->string('level', 2);
            $table->unsignedInteger('completed_turns');
            $table->unsignedInteger('spoken_turns');
            $table->unsignedInteger('assist_count');
            $table->boolean('used_repair_strategy')->default(false);
            $table->unsignedInteger('earned_xp')->default(0);
            $table->unsignedInteger('earned_confianza')->default(0);
            $table->unsignedInteger('earned_valentia')->default(0);
            $table->json('evidence');
            $table->timestamp('completed_at', 6);
            $table->timestamps(precision: 6);

            $table->unique(['user_id', 'mission_key', 'attempt_number']);
            $table->unique(['user_id', 'completion_key']);
            $table->index(['user_id', 'status', 'completed_at']);
        });

        Schema::create('game_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 30);
            $table->bigInteger('amount_delta');
            $table->unsignedBigInteger('balance_after');
            $table->string('reason_type', 60);
            $table->unsignedBigInteger('reason_id')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at', 6);

            $table->index(['user_id', 'currency', 'created_at']);
            $table->index(['reason_type', 'reason_id']);
        });

        Schema::create('user_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mission_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('mission_key', 120);
            $table->string('reward_key', 120);
            $table->string('reward_type', 40);
            $table->string('title_es');
            $table->string('title_nl');
            $table->json('metadata')->nullable();
            $table->timestamp('first_acquired_at', 6);

            $table->unique(['user_id', 'reward_key']);
            $table->index(['user_id', 'mission_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('game_ledger');
        Schema::dropIfExists('mission_attempts');
        Schema::dropIfExists('user_mission_progress');
        Schema::dropIfExists('user_game_states');
    }
};
