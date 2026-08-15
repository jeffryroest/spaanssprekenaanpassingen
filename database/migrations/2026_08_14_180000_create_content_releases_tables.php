<?php

use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_releases', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('target_channel', 24)->default(ContentReleaseChannel::Preview->value);
            $table->dateTime('desired_publish_at', precision: 6)->nullable();
            $table->string('status', 24)->default(ContentReleaseStatus::Draft->value);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at', precision: 6)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at', precision: 6)->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps(precision: 6);

            $table->index(['status', 'desired_publish_at'], 'content_releases_planning_index');
            $table->index(['target_channel', 'published_at'], 'content_releases_channel_index');
        });

        Schema::create('content_release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_node_id')->constrained()->restrictOnDelete();
            $table->foreignId('content_revision_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at', precision: 6);

            $table->unique(['content_release_id', 'content_node_id'], 'content_release_items_node_unique');
            $table->index(['content_node_id', 'version'], 'content_release_items_version_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_release_items');
        Schema::dropIfExists('content_releases');
    }
};
