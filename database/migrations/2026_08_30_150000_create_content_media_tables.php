<?php

use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('kind', array_map(static fn (MediaKind $kind): string => $kind->value, MediaKind::cases()));
            $table->string('disk', 80);
            $table->string('object_key', 500)->unique();
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('byte_size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum_sha256', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('alt_text')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('source_name')->nullable();
            $table->string('creator_name')->nullable();
            $table->string('license_name')->nullable();
            $table->enum('rights_status', array_map(
                static fn (MediaRightsStatus $status): string => $status->value,
                MediaRightsStatus::cases(),
            ));
            $table->date('rights_expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(precision: 6);
            $table->softDeletes('deleted_at', precision: 6);

            $table->index(['kind', 'rights_status', 'created_at'], 'media_assets_library_index');
            $table->index('checksum_sha256');
        });

        Schema::create('content_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->string('role', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps(precision: 6);

            $table->unique(['content_revision_id', 'role']);
            $table->index(['content_node_id', 'content_revision_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
        Schema::dropIfExists('media_assets');
    }
};
