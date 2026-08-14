<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_revision_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('action', 32);
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->nullable();
            $table->dateTime('created_at', precision: 6);

            $table->index(['content_node_id', 'version', 'created_at'], 'content_reviews_version_history_index');
            $table->index(['action', 'created_at'], 'content_reviews_action_queue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reviews');
    }
};
