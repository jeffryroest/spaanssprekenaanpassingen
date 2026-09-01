<?php

namespace App\ContentStudio;

use App\Actions\ContentStudio\CreateMediaAsset;
use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class GoldenRouteMedia
{
    public function __construct(private readonly CreateMediaAsset $createMediaAsset) {}

    /** @return array<string, array{path: string, original_name: string, title: string, description: string, alt_text: string}> */
    public function manifest(): array
    {
        return [
            'madrid_morning' => [
                'path' => resource_path('game-assets/golden-route/madrid-morning.webp'),
                'original_name' => 'madrid-morning.webp',
                'title' => 'Madrid in de ochtend',
                'description' => 'Brede wereldillustratie voor de aankomst en buurtkaart van de gouden route.',
                'alt_text' => 'Een warme geïllustreerde Madrileense buurt in de ochtend, met La Espiga aan een rustig plein.',
            ],
            'la_espiga_interior' => [
                'path' => resource_path('game-assets/golden-route/la-espiga-interior.webp'),
                'original_name' => 'la-espiga-interior.webp',
                'title' => 'Interieur van La Espiga',
                'description' => 'Brede scèneachtergrond voor het eerste gesprek met Lucía.',
                'alt_text' => 'Een warme Madrileense bakkerij met houten broodrekken, blauwe tegels en ochtendlicht bij de toonbank.',
            ],
            'lucia_expressions' => [
                'path' => resource_path('game-assets/golden-route/lucia-expressions.webp'),
                'original_name' => 'lucia-expressions.webp',
                'title' => 'Lucía · drie reacties',
                'description' => 'Karakterblad met luisteren, aanmoedigen en het vieren van de geslaagde bestelling.',
                'alt_text' => 'Lucía, de bakker, luistert aandachtig, moedigt de speler aan en overhandigt daarna een broodzak.',
            ],
            'madrid_station_hall' => [
                'path' => resource_path('game-assets/golden-route/madrid-station-hall.webp'),
                'original_name' => 'madrid-station-hall.webp',
                'title' => 'Estación del Centro',
                'description' => 'Brede stationshal voor het dag-6-gesprek met Mateo.',
                'alt_text' => 'Een warme geïllustreerde Madrileense stationshal met loket, glazen dak en doorgang naar de perrons.',
            ],
            'mateo_station_expressions' => [
                'path' => resource_path('game-assets/golden-route/mateo-station-expressions.webp'),
                'original_name' => 'mateo-station-expressions.webp',
                'title' => 'Mateo · drie reacties',
                'description' => 'Karakterblad met luisteren, uitleggen en de overdracht van het fictieve treinkaartje.',
                'alt_text' => 'Stationsmedewerker Mateo luistert aandachtig, legt een keuze uit en overhandigt daarna een treinkaartje.',
            ],
        ];
    }

    /** @return array<string, string> */
    public function rolesForTemplate(string $templateKey): array
    {
        return match ($templateKey) {
            'madrid-hub' => ['map_background' => 'madrid_morning'],
            'panaderia' => [
                'scene_background' => 'la_espiga_interior',
                'npc_expression_sheet' => 'lucia_expressions',
            ],
            'final' => [
                'scene_background' => 'la_espiga_interior',
                'npc_expression_sheet' => 'lucia_expressions',
            ],
            'station' => [
                'scene_background' => 'madrid_station_hall',
                'npc_expression_sheet' => 'mateo_station_expressions',
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>  $keys
     * @return Collection<string, MediaAsset>
     */
    public function ensure(User $actor, array $keys): Collection
    {
        $manifest = $this->manifest();

        return collect($keys)->unique()->mapWithKeys(function (string $key) use ($actor, $manifest): array {
            $definition = $manifest[$key];
            $checksum = hash_file('sha256', $definition['path']);
            $existing = MediaAsset::query()
                ->where('checksum_sha256', $checksum)
                ->where('title', $definition['title'])
                ->where('rights_status', MediaRightsStatus::Owned->value)
                ->get()
                ->first(fn (MediaAsset $asset): bool => Storage::disk($asset->disk)->exists($asset->object_key));

            if ($existing !== null) {
                return [$key => $existing];
            }

            $file = new UploadedFile(
                $definition['path'],
                $definition['original_name'],
                'image/webp',
                UPLOAD_ERR_OK,
                true,
            );

            return [$key => $this->createMediaAsset->handle(
                actor: $actor,
                file: $file,
                kind: MediaKind::Image,
                title: $definition['title'],
                description: $definition['description'],
                altText: $definition['alt_text'],
                transcript: null,
                rightsStatus: MediaRightsStatus::Owned,
                sourceName: 'Spaansspreken.nl eigen spelillustraties',
                creatorName: 'OpenAI ImageGen onder productregie',
                licenseName: null,
                rightsExpiresAt: null,
            )];
        });
    }

    public function checksum(string $key): string
    {
        return hash_file('sha256', $this->manifest()[$key]['path']);
    }
}
