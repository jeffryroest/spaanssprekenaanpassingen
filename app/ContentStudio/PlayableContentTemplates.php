<?php

namespace App\ContentStudio;

use App\Enums\ContentType;
use JsonException;
use RuntimeException;

final class PlayableContentTemplates
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return [
            'madrid-hub' => $this->template(
                key: 'madrid-hub',
                label: 'Madrid-wereld',
                description: 'Vier locaties, drie ontdekkingen en de route naar La Espiga.',
                contentType: ContentType::Region,
                slug: 'madrid',
                title: 'Madrid',
                summary: 'De speelbare Madrid-hub voor de eerste missies.',
                exampleFile: 'madrid-hub-domain-data.json',
            ),
            'panaderia' => $this->template(
                key: 'panaderia',
                label: 'La Espiga-gesprek',
                description: 'De openbare bakkerijmissie met Lucía en drie niveaupaden.',
                contentType: ContentType::ConversationScenario,
                slug: 'la-espiga-lucia',
                title: 'La Espiga · Lucía',
                summary: 'Vertakkend gesprek voor een eerste bestelling in Madrid.',
                exampleFile: 'panaderia-dialogue-domain-data.json',
            ),
            'taxi' => $this->template(
                key: 'taxi',
                label: 'Taxigesprek',
                description: 'De afgeschermde proefweekmissie met Diego.',
                contentType: ContentType::ConversationScenario,
                slug: 'taxi-diego',
                title: 'En taxi · Diego',
                summary: 'Vertakkend gesprek voor een taxirit door Madrid.',
                exampleFile: 'taxi-dialogue-domain-data.json',
            ),
            'restaurant' => $this->template(
                key: 'restaurant',
                label: 'Restaurantgesprek',
                description: 'De afgeschermde proefweekmissie met Carmen in Café El Reloj.',
                contentType: ContentType::ConversationScenario,
                slug: 'restaurant-el-reloj',
                title: 'En el restaurante · Carmen',
                summary: 'Vertakkend gesprek voor een tafel, bestelling en rekening in Madrid.',
                exampleFile: 'restaurant-dialogue-domain-data.json',
            ),
            'health' => $this->template(
                key: 'health',
                label: 'Gesprek in de consulta',
                description: 'De afgeschermde proefweekmissie met arts Elena en een fictieve rolkaart.',
                contentType: ContentType::ConversationScenario,
                slug: 'consulta-elena',
                title: 'En la consulta · Elena',
                summary: 'Vertakkend rollenspel om een klacht uit te leggen en om schriftelijke uitleg te vragen.',
                exampleFile: 'health-dialogue-domain-data.json',
            ),
            'station' => $this->template(
                key: 'station',
                label: 'Stationsgesprek',
                description: 'De afgeschermde proefweekmissie met Nuria aan de kaartverkoop van Atocha.',
                contentType: ContentType::ConversationScenario,
                slug: 'station-nuria',
                title: 'En la estación · Nuria',
                summary: 'Vertakkend gesprek om een retourticket naar Toledo te regelen.',
                exampleFile: 'station-dialogue-domain-data.json',
            ),
        ];
    }

    /** @return array<string, mixed>|null */
    public function find(?string $key): ?array
    {
        return $key === null ? null : ($this->all()[$key] ?? null);
    }

    /** @return array<string, mixed> */
    private function template(
        string $key,
        string $label,
        string $description,
        ContentType $contentType,
        string $slug,
        string $title,
        string $summary,
        string $exampleFile,
    ): array {
        $path = base_path("content/examples/{$exampleFile}");
        $source = file_get_contents($path);

        if ($source === false) {
            throw new RuntimeException("Speeltemplate {$exampleFile} kon niet worden gelezen.");
        }

        try {
            $domainData = json_decode($source, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Speeltemplate {$exampleFile} bevat ongeldige JSON.", previous: $exception);
        }

        if (! is_array($domainData)) {
            throw new RuntimeException("Speeltemplate {$exampleFile} moet een JSON-object bevatten.");
        }

        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'content_type' => $contentType,
            'slug' => $slug,
            'locale' => 'es-ES',
            'title' => $title,
            'summary' => $summary,
            'body' => null,
            'domain_data' => $domainData,
        ];
    }
}
