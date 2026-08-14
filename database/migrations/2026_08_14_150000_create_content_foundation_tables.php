<?php

use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 40);
            $table->string('slug', 180);
            $table->enum('status', array_map(
                static fn (ContentStatus $status): string => $status->value,
                ContentStatus::cases(),
            ))
                ->default(ContentStatus::Draft->value);
            $table->string('default_locale', 10)->default('es-ES');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->unsignedInteger('current_version')->default(1);
            $table->dateTime('published_at', precision: 6)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(precision: 6);
            $table->softDeletes('deleted_at', precision: 6);

            $table->unique(['content_type', 'slug']);
            $table->index(['status', 'content_type', 'updated_at'], 'content_nodes_work_queue_index');
            $table->index(['content_type', 'published_at'], 'content_nodes_published_index');
        });

        Schema::create('content_localizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_node_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps(precision: 6);

            $table->unique(['content_node_id', 'locale']);
            $table->index(['locale', 'title']);
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_node_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('status', array_map(
                static fn (RevisionStatus $status): string => $status->value,
                RevisionStatus::cases(),
            ))
                ->default(RevisionStatus::Draft->value);
            $table->json('snapshot');
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at', precision: 6)->nullable();
            $table->dateTime('created_at', precision: 6);

            $table->unique(['content_node_id', 'version']);
            $table->index(['status', 'created_at'], 'content_revisions_review_queue_index');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                ALTER TABLE content_nodes
                ADD CONSTRAINT content_nodes_published_at_check
                CHECK (status <> 'published' OR published_at IS NOT NULL)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('content_localizations');
        Schema::dropIfExists('content_nodes');
    }
};
