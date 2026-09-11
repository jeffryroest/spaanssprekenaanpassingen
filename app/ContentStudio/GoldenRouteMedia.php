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
            'madrid_taxi_interior' => [
                'path' => resource_path('game-assets/golden-route/madrid-taxi-interior.webp'),
                'original_name' => 'madrid-taxi-interior.webp',
                'title' => 'Taxirit door Madrid',
                'description' => 'Brede scèneachtergrond voor het dag-2-gesprek met Diego.',
                'alt_text' => 'Uitzicht vanuit een taxi op een zonnige boulevard in Madrid, met het dashboard en de binnenspiegel op de voorgrond.',
            ],
            'diego_taxi_expressions' => [
                'path' => resource_path('game-assets/golden-route/diego-taxi-expressions.webp'),
                'original_name' => 'diego-taxi-expressions.webp',
                'title' => 'Diego · drie reacties',
                'description' => 'Karakterblad met luisteren, aanmoedigen en het vieren van de geslaagde taxirit.',
                'alt_text' => 'Taxichauffeur Diego luistert aandachtig, moedigt de speler aan en viert daarna de geslaagde rit.',
            ],
            'cafe_el_reloj_interior' => [
                'path' => resource_path('game-assets/golden-route/cafe-el-reloj-interior.webp'),
                'original_name' => 'cafe-el-reloj-interior.webp',
                'title' => 'Interieur van Café El Reloj',
                'description' => 'Brede scèneachtergrond voor het dag-3-gesprek met Carmen.',
                'alt_text' => 'Een warm verlicht Madrileens restaurant met gedekte tafels, blauwe tegels en een houten bar.',
            ],
            'carmen_restaurant_expressions' => [
                'path' => resource_path('game-assets/golden-route/carmen-restaurant-expressions.webp'),
                'original_name' => 'carmen-restaurant-expressions.webp',
                'title' => 'Carmen · drie reacties',
                'description' => 'Karakterblad met luisteren, aanmoedigen en het vieren van het geslaagde restaurantgesprek.',
                'alt_text' => 'Serveerster Carmen luistert aandachtig, moedigt de speler aan en presenteert daarna glimlachend de rekeningmap.',
            ],
            'consulta_la_luz_interior' => [
                'path' => resource_path('game-assets/golden-route/consulta-la-luz-interior.webp'),
                'original_name' => 'consulta-la-luz-interior.webp',
                'title' => 'Spreekkamer van Consulta La Luz',
                'description' => 'Brede scèneachtergrond voor het fictieve dag-5-rollenspel met Elena.',
                'alt_text' => 'Een rustige lichte spreekkamer in Madrid met een houten bureau, twee groene stoelen en een onderzoeksbank.',
            ],
            'elena_doctor_expressions' => [
                'path' => resource_path('game-assets/golden-route/elena-doctor-expressions.webp'),
                'original_name' => 'elena-doctor-expressions.webp',
                'title' => 'Elena · drie reacties',
                'description' => 'Karakterblad met luisteren, geruststellen en het vieren van het geslaagde taalrollenspel.',
                'alt_text' => 'Arts Elena luistert aandachtig, stelt de speler gerust en viert daarna het geslaagde fictieve taalrollenspel.',
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
            'taxi' => [
                'scene_background' => 'madrid_taxi_interior',
                'npc_expression_sheet' => 'diego_taxi_expressions',
            ],
            'restaurant' => [
                'scene_background' => 'cafe_el_reloj_interior',
                'npc_expression_sheet' => 'carmen_restaurant_expressions',
            ],
            'health' => [
                'scene_background' => 'consulta_la_luz_interior',
                'npc_expression_sheet' => 'elena_doctor_expressions',
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
