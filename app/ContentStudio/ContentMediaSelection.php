<?php

namespace App\ContentStudio;

use App\Enums\ContentType;
use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ContentMediaSelection
{
    public function __construct(private readonly ContentMediaRoles $roles) {}

    /**
     * @param  array<string, int|string|null>  $selection
     * @return Collection<string, MediaAsset>
     */
    public function resolve(ContentType $contentType, array $selection): Collection
    {
        $allowedRoles = $this->roles->for($contentType);
        $selectedIds = collect($selection)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id);

        foreach ($selectedIds as $role => $id) {
            if (! array_key_exists((string) $role, $allowedRoles)) {
                throw ValidationException::withMessages([
                    "media.{$role}" => 'Deze mediarol past niet bij het gekozen contenttype.',
                ]);
            }
        }

        $assets = MediaAsset::query()
            ->whereIn('id', $selectedIds->values())
            ->get()
            ->keyBy('id');

        $resolved = collect();
        foreach ($selectedIds as $role => $id) {
            $asset = $assets->get($id);
            if ($asset === null) {
                throw ValidationException::withMessages([
                    "media.{$role}" => 'Het gekozen mediabestand bestaat niet meer.',
                ]);
            }

            if ($asset->kind !== $allowedRoles[$role]['kind']) {
                throw ValidationException::withMessages([
                    "media.{$role}" => "Voor {$allowedRoles[$role]['label']} is {$allowedRoles[$role]['kind']->label()} vereist.",
                ]);
            }

            $resolved->put((string) $role, $asset);
        }

        return $resolved;
    }
}
