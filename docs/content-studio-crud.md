# Content Studio basis-CRUD

Fase 1C maakt het canonieke contentfundament bruikbaar via een beveiligde beheerinterface. Deze slice volgt de rollen, navigatie, catalogusregels en versieprincipes uit `docs/content-studio.md`.

## Mogelijkheden

- alle Content Studio-rollen kunnen de catalogus en details bekijken;
- zoeken op titel, inhoud, slug en numeriek ID;
- filteren op contenttype en workflowstatus;
- gebruikers met de permissie `edit` kunnen een concept aanmaken;
- een Redacteur kan alleen eigen concepten bewerken of archiveren; Beheerder en Hoofdredacteur kunnen alle concepten beheren;
- bewerken maakt altijd een nieuwe onveranderlijke conceptrevisie;
- verwijderen is vervangen door veilig archiveren met een verplichte reden;
- iedere geslaagde creatie, bewerking en archivering schrijft een append-only auditregel.

De interface verbergt schrijfknoppen voor read-only rollen. Controllers, Form Requests én domeinacties dwingen dezelfde permissies server-side af.

In deze fase geldt `created_by` als eigenaar van een concept. Een afzonderlijk toewijzingsmodel volgt later; er wordt nu geen impliciete gedeelde redactie voor gewone Redacteurs toegestaan.

## Versieveiligheid

Een bewerkings- of archiveringsformulier bevat de versie die bij het openen actueel was. De domeinactie vergrendelt het contentrecord in een databasetransactie en vergelijkt die versie opnieuw. Als een andere redacteur intussen heeft opgeslagen, wordt de verouderde actie geweigerd zonder gedeeltelijke wijzigingen.

Alleen de statussen `draft` en `changes_requested` zijn in deze slice bewerkbaar. Gepubliceerde content aanpassen vereist later de volledige review- en releaseworkflow en wordt nu bewust niet toegestaan.

## Auditlog

De migratie voegt de canonieke tabel `audit_logs` toe. Voor inhoudswijzigingen worden minimaal actor, actorrol, actie, content-ID, vorige toestand, nieuwe toestand, correlatie-ID en UTC-tijd geregistreerd. Ruwe IP-adressen, volledige user agents, wachtwoorden en andere gevoelige waarden worden niet opgeslagen.

Auditregels kunnen niet via Eloquent worden gewijzigd of verwijderd. Databasebeheerders houden toegang voor herstel en wettelijke beheerprocessen buiten de reguliere interface.

## Bewuste afbakening

- nog geen review aanvragen, goedkeuren of wijzigingen vragen;
- nog geen publicatie, releases of publieke content-API;
- nog geen importcentrum of staged records;
- nog geen typespecifieke editors of tabellen;
- nog geen Madrid-seeders.
- nog geen centraal audit-event voor geweigerde acties; geslaagde inhoudswijzigingen en alle roltoewijzingen worden al vastgelegd.

## Deployment en terugrol

```bash
php artisan migrate --force
php artisan optimize
```

Controleer daarna:

```bash
php artisan migrate:status
php artisan test
```

Terugrollen kan samen met een code-rollback via `php artisan migrate:rollback --step=1`. Dit verwijdert alleen `audit_logs`, maar daarmee verdwijnt wel de volledige inhoudelijke auditgeschiedenis. Maak daarom vooraf een databaseback-up.

## Acceptatiecriteria

- een gast wordt naar login gestuurd en een gebruiker zonder Content Studio-rol krijgt 403;
- read-only rollen kunnen de catalogus en details bekijken, maar geen schrijfroute gebruiken;
- nieuwe content start als concept met revisie 1 en een auditregel;
- een bewerking maakt revisie `n + 1` en laat eerdere snapshots intact;
- een verouderde bewerking wordt atomair geweigerd;
- archiveren behoudt het contentrecord en de revisiegeschiedenis;
- auditregels zijn via het applicatiemodel onveranderlijk;
- er bestaat nog geen publieke route naar conceptcontent.
