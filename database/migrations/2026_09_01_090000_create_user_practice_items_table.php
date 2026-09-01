<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_practice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('practice_key', 64);
            $table->string('source_mission_key', 120);
            $table->foreignId('source_content_node_id')->nullable()->constrained('content_nodes')->nullOnDelete();
            $table->unsignedInteger('source_content_version');
            $table->string('step_id', 80);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->unsignedSmallInteger('successful_repetitions')->default(0);
            $table->unsignedSmallInteger('lapse_count')->default(0);
            $table->string('last_rating', 10)->nullable();
            $table->timestamp('due_at', 6);
            $table->timestamp('last_practiced_at', 6)->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['user_id', 'practice_key']);
            $table->index(['user_id', 'due_at']);
            $table->index(
                ['source_content_node_id', 'source_content_version'],
                'practice_source_version_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_practice_items');
    }
};
