# Canoniek contentfundament

Fase 1B introduceert de generieke publicatie-envelop voor alle redactionele content. De implementatie volgt `database/schema.sql`, het domeinmodel en ADR-001.

## Tabellen

- `content_nodes`: stabiele identiteit, type, slug, workflowstatus, schema- en contentversie en auteursvelden;
- `content_localizations`: titel, samenvatting, body en metadata per locale;
- `content_revisions`: onveranderlijke JSON-snapshot per ingediende versie.

De migratie gebruikt microseconden voor applicatietijden. `published` vereist zowel in de applicatielaag als in MySQL een niet-leeg `published_at`.

## Veilige creatie

`CreateDraftContent` is de enige service in deze slice waarmee handmatige content wordt aangemaakt. De service:

1. valideert slug, locale en titel;
2. vereist de bestaande Content Studio-permissie `edit`;
3. maakt altijd status `draft` aan;
4. schrijft de eerste lokalisatie;
5. schrijft revisie 1 als onveranderlijke snapshot;
6. voert dit alles in één databasetransactie uit.

Er bestaat nog geen publieke of beheer-HTTP-route om content te maken. De eerste CRUD-interface volgt nadat dit fundament op MySQL is gevalideerd.

## Bewuste afbakening

- `staged` is geen contentstatus en blijft gereserveerd voor toekomstige importrecords.
- Publiceren, reviewtransities en releases zijn nog niet geïmplementeerd.
- Typespecifieke tabellen voor woorden, regio's, locaties, NPC's, missies en gesprekken volgen thematisch.
- Madrid → La panadería wordt pas geseed zodra de vereiste wereld- en gesprekstabellen bestaan; er worden geen incomplete productierecords gemaakt.
- Externe content wordt niet geïmporteerd of automatisch gepubliceerd.

## Deployment en terugrol

```bash
php artisan migrate --force
php artisan optimize
```

Controleer daarna:

```bash
php artisan migrate:status
```

Terugrollen kan met `php artisan migrate:rollback --step=1`. Dit verwijdert de drie nieuwe tabellen inclusief concepten en revisies; maak daarom vooraf een databaseback-up.

## Acceptatiecriteria

- Nieuwe handmatige content start altijd als concept.
- Alleen een actor met de Content Studio-permissie `edit` kan conceptcontent maken.
- Een concept krijgt in dezelfde transactie een lokalisatie en eerste revisie.
- Slugs zijn uniek per contenttype.
- `staged` kan niet als contentstatus worden opgeslagen.
- Een gepubliceerde envelop zonder `published_at` wordt geweigerd.
- Een bestaande revisie kan niet via Eloquent worden aangepast of afzonderlijk verwijderd.
- Er is geen publieke contentroute.
