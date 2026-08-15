# Publieke content-API v1

Fase 1F levert een versieerbare, read-only runtime-API voor de interactieve Spaanse wereld. De API is publiek leesbaar, maar geeft uitsluitend content vrij waarvoor een actuele, uitgevoerde productierelease bestaat. De Content Studio en haar revisies blijven de bron van waarheid.

## Routes

Alle routes vallen onder `/api/v1`, accepteren `application/json` en vereisen geen authenticatie.

| Runtimebron | Content Studio-type | Collectie | Detail |
|---|---|---|---|
| Werelden | `region` | `GET /api/v1/worlds` | `GET /api/v1/worlds/{slug}` |
| Locaties | `location` | `GET /api/v1/locations` | `GET /api/v1/locations/{slug}` |
| Missies | `mission` | `GET /api/v1/missions` | `GET /api/v1/missions/{slug}` |
| Gesprekken | `conversation_scenario` | `GET /api/v1/conversations` | `GET /api/v1/conversations/{slug}` |

De limiet is 120 aanvragen per minuut per client. Collecties ondersteunen `page` en `per_page`; toegestane paginagroottes zijn 10, 20 en 50, met 20 als standaard.

## Publicatiegrens

Een record komt alleen in de API wanneer al deze voorwaarden tegelijk gelden:

1. het contentobject heeft status `published` en een bereikt `published_at`;
2. een release-item verwijst naar exact `current_version` van het contentobject;
3. de gekoppelde release heeft doelkanaal `production`, status `published` en een bereikt publicatietijdstip;
4. de gekoppelde onveranderlijke revisie hoort bij hetzelfde contentobject en dezelfde versie.

De payload wordt opgebouwd uit de snapshot van die exacte revisie, niet uit later gewijzigde werkvelden of lokalisatierijen. Alleen een handmatig gewijzigde contentstatus is daardoor nooit voldoende om content publiek te maken. Bij inconsistente publicatiegegevens faalt de API gesloten met een lege collectie of een 404.

## Versie en contract

Iedere JSON-response bevat `schema_version: "1.0.0"` en iedere respons bevat de header `X-Content-API-Version: 1.0.0`. Het machineleesbare contract staat in [`docs/contracts/public-content-api-v1.schema.json`](contracts/public-content-api-v1.schema.json).

Een detailresponse heeft deze vorm:

```json
{
  "schema_version": "1.0.0",
  "data": {
    "id": 42,
    "type": "mission",
    "slug": "bestel-een-cafe",
    "version": 1,
    "content_schema_version": 1,
    "requested_locale": "nl-NL",
    "locale": "es-ES",
    "available_locales": ["es-ES"],
    "published_at": "2026-08-15T10:00:00+00:00",
    "publication": {
      "release_id": 7,
      "published_at": "2026-08-15T10:00:00+00:00"
    },
    "links": {
      "self": "https://v2.spaansspreken.nl/api/v1/missions/bestel-een-cafe"
    },
    "content": {
      "title": "Bestel een café",
      "summary": "Oefen een bestelling in Madrid.",
      "body": "Volg de stappen en rond het gesprek af.",
      "metadata": {},
      "domain_data": {
        "difficulty": "starter"
      }
    }
  }
}
```

Collecties gebruiken dezelfde identiteit in compacte vorm met `title` en `summary`, plus paginering en navigatielinks. Auditregels, gebruikers, reviewnotities en andere beheergegevens worden niet opgenomen.

## Lokalisatie

De optionele queryparameter `locale` gebruikt een BCP 47-vorm zoals `es-ES` of `nl-NL`. De volgorde is:

1. de gevraagde lokalisatie uit de gepubliceerde snapshot;
2. de gepubliceerde standaardlokalisatie;
3. de eerste geldige lokalisatie uit die snapshot.

`requested_locale` bewaart de vraag van de client en `locale` vermeldt welke taal werkelijk is geleverd.

## Caching en fouten

Succesvolle responses zijn kort publiek cachebaar en bevatten een inhoudsgebonden `ETag`. Een bijpassende `If-None-Match` levert 304 zonder responsebody. `Last-Modified` vermeldt het publicatietijdstip en `Vary` beschermt representatievarianten.

Fouten gebruiken dezelfde API-versie en een stabiele vorm:

```json
{
  "schema_version": "1.0.0",
  "error": {
    "code": "published_content_not_found",
    "message": "De gepubliceerde mission is niet gevonden."
  }
}
```

Validatiefouten voegen `error.details` toe. Foutresponses krijgen `Cache-Control: no-store`.

## Bewuste afbakening

- geen mutaties of beheerroutes;
- geen spelerstatus, voortgang of beloningen;
- nog geen typed relationele runtimegraph tussen wereld, locatie, missie en gesprek;
- nog geen audio-, transcriptie- of feedback-endpoints;
- nog geen terugtrek- of rollbackpublicatie.

Deze API maakt de eerste gameclient mogelijk zonder toekomstige typed domeintabellen vooruit te lopen. `domain_data` blijft in v1 bewust een versiegebonden uitbreidingsobject.

## Deployment

Deze fase voegt geen migratie toe. Na het ophalen van de code:

```bash
php artisan optimize
php artisan test
```

Controleer daarna minimaal:

```bash
curl -i https://v2.spaansspreken.nl/api/v1/worlds
curl -i https://v2.spaansspreken.nl/api/v1/missions
```

Een lege `data`-lijst is correct zolang nog geen content via een productierelease is gepubliceerd.

## Acceptatiecriteria

- gasten kunnen de vier runtimebronnen als collectie en detail lezen;
- concept-, review-, goedgekeurde, preview- en stagingcontent blijft onzichtbaar;
- alleen de actuele productiegepubliceerde revisiesnapshot wordt geleverd;
- locale-fallback, paginering, ETag en uniforme fouten zijn getest;
- het JSON Schema en de runtimeversie lopen gelijk;
- responses lekken geen audit-, review- of actorinformatie.
