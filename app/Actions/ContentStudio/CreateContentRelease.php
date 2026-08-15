<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Models\AuditLog;
use App\Models\ContentRelease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateContentRelease
{
    public function handle(
        User $actor,
        string $name,
        ContentReleaseChannel $targetChannel,
        ?string $description = null,
        ?string $desiredPublishAt = null,
    ): ContentRelease {
        Gate::forUser($actor)->authorize('content-studio.publish');

        $validated = Validator::make([
            'name' => $name,
            'description' => $description,
            'desired_publish_at' => $desiredPublishAt,
        ], [
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'desired_publish_at' => ['nullable', 'date'],
        ])->validate();

        return DB::transaction(function () use ($actor, $targetChannel, $validated): ContentRelease {
            $release = ContentRelease::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'target_channel' => $targetChannel,
                'desired_publish_at' => $validated['desired_publish_at'] ?? null,
                'status' => ContentReleaseStatus::Draft,
                'owner_user_id' => $actor->getKey(),
            ]);

            AuditLog::recordReleaseChange(
                actor: $actor,
                action: 'content.release_created',
                release: $release,
                before: null,
                after: $this->state($release),
            );

            return $release;
        });
    }

    /** @return array<string, mixed> */
    private function state(ContentRelease $release): array
    {
        return [
            'name' => $release->name,
            'status' => $release->status->value,
            'target_channel' => $release->target_channel->value,
            'desired_publish_at' => $release->desired_publish_at?->toAtomString(),
            'item_count' => 0,
        ];
    }
}
