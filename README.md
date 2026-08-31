# Spaansspreken.nl

Spaansspreken.nl wordt een visuele, interactieve Spaanse wereld waarin Nederlandse gebruikers leren handelen en spreken in realistische situaties. De eerste complete vertical slice speelt zich af in Madrid en eindigt in een gesprek bij **La panadería**.

## Productuitgangspunten

- Spreken en jezelf verstaanbaar maken staan centraal.
- De wereld, personages en gevolgen maken leren betekenisvol.
- De Content Studio is de enige bron van waarheid voor gepubliceerde leercontent.
- Externe woordenlijsten zijn optionele inspiratie en komen altijd eerst in een import-stagingomgeving.
- Geen enkele import wordt automatisch gepubliceerd.
- Audio-opnames blijven in het oorspronkelijke WebM-formaat; ffmpeg is geen vereiste.
- Eerst één uitzonderlijk goede vertical slice, daarna gecontroleerd uitbreiden.

## Eerste productmijlpaal

Een nieuwe gebruiker kan:

1. Madrid betreden;
2. een eerste missie accepteren;
3. woorden en zinnen voorbereiden;
4. bij een bakker een vertakkend gesprek voeren;
5. minimaal één antwoord inspreken;
6. begrijpelijke feedback ontvangen;
7. voortgang en een Spaanse beloning opslaan.

## Beoogde stack

- PHP/Laravel
- MySQL
- Tailwind CSS
- NGINX
- WebM-opname in de browser
- Losse adapters voor transcriptie, gespreksgeneratie en beoordeling

De definitieve frameworkversies worden pas vastgezet wanneer de uitvoeringsomgeving en lifecycle zijn bevestigd.

## Projectstatus

Fase 0: fundament, domeinmodel, Content Studio, importcontract en specificatie van Madrid → La panadería.

Fase 1A: beveiligde Content Studio-toegang met login, redactierollen, server-side autorisatie en audit van roltoewijzingen.

Fase 1B: canonieke content-envelop, lokalisaties en onveranderlijke revisies voor veilige conceptcontent.

Fase 1C: beveiligde contentcatalogus en basis-CRUD met zoeken, filters, nieuwe revisies, veilig archiveren en inhoudelijk auditlog.

Fase 1C.1: responsief Content Studio-designsystem met gedeelde navigatie, componenten en toegankelijke interactiepatronen.

Fase 1D: versiegebonden reviewworkflow met reviewwachtrij, vier-ogencontrole, gemotiveerde beslissingen en append-only historie.

Fase 1E: versiegebonden releases met kanaalpreflight, veilige preview/staging-uitvoering en expliciet bevestigde productiepublicatie.

Fase 1F: publieke read-only content-API v1 voor uitsluitend actuele, productiegepubliceerde werelden, locaties, missies en gesprekken.

Fase 1G: GitHub Actions-kwaliteitsstraat met PHP-formattering, dependency-audits, frontendbuild en Laravel-tests op MySQL.

Fase 2A: publieke, data-gedreven Madrid-hub met vier locaties, onderzoekspunten en een toegankelijke kaart-/lijstweergave.

Fase 2B: Panadería La Espiga als betreedbare locatie met Lucía, vijf hervatbare tekstbeurten en drie niveaugebonden complicaties.

Fase 2C: expliciete WebM/Opus-opname van maximaal 12 seconden, lokaal terugluisteren, veilige Spaanse transcriptie, confidence-waarschuwing en blijvende tekstfallback.

Fase 2D: servergevalideerde feedback op de gepubliceerde beurtcontext, met communicatief succes voorop, vijf tekstbewijs-rubrics, één concrete focus, expliciet onbeoordeelde uitspraak en een rollback-veilige herkansing.

Fase 2E: duurzame accountvoortgang met server-side routevalidatie, idempotente XP/Confianza/Valentía, spreekdoel, unieke beloningen, Madrid-ontgrendelingen en een eigen voortgangsdashboard zonder opslag van audio, transcript of AI-feedback.

Fase 3A: zeven-dagenroute, canonieke abonnementsprojectie, centrale server-side rechtenservice, toegankelijke proefweekweergave en een privacybewust toegangscontract; prijs, checkout en automatische proefactivatie blijven uit tot een expliciet productbesluit.

Fase 3B1: afgeschermde taximissie met Diego, vijf actieve beurten, A0/A1/A2-routevragen, herbruikbare spraak- en feedbackmotor, servergevalideerde accountbeloningen en een Content Studio-gebonden dag-2-ontgrendeling.

Fase 3B1.5: spelersgerichte startpagina, geïllustreerde Madrid-wereld, aankomst en missievoorbereiding, zichtbare wereldreactie, Content Studio-starters en productie-assetcontrole.

Fase 3B2: afgeschermde restaurantmissie met Carmen in Café El Reloj, vijf actieve beurten, A0/A1/A2-bedieningsvragen, een warme restaurantscène, herbruikbare spraak- en feedbackmotor en servergevalideerde dag-3-beloningen.

Fase 3B2.5: demo-ready Content Studio met risicogestuurde review, veilige reviewintrekking, inhoudelijke speelbaarheidscontrole en een idempotent conceptpakket voor de volledige huidige Madrid-demo.

Fase 3B3: afgeschermd gezondheidsrollenspel met Elena in Consulta La Luz, een vaste fictieve rolkaart, vijf actieve beurten, A0/A1/A2-vervolgvragen, lokale redactie van gevoelige antwoordtekst en servergevalideerde dag-5-beloningen zonder opslag van gezondheidsinformatie.

Fase 3B4: auteurvriendelijke wereld-, NPC- en gespreksbouwer boven de bestaande contracten, volledige routegrafiekvalidatie, een privémediabibliotheek met rechten- en toegankelijkheidscontrole en een niet-indexeerbare spelpreview die geen voortgang schrijft.

Zie [AGENTS.md](AGENTS.md) voor de samenwerking, [docs/roadmap.md](docs/roadmap.md) voor de ontwikkelvolgorde, [docs/content-builder-preview.md](docs/content-builder-preview.md) voor de speelcontentbouwer, media en preview, [docs/demo-ready-content-studio.md](docs/demo-ready-content-studio.md) voor review en het voorbeeldpakket, [docs/webm-transcription.md](docs/webm-transcription.md) voor de spraaklaag, [docs/layered-turn-feedback.md](docs/layered-turn-feedback.md) voor de feedbackgrens, [docs/account-progress.md](docs/account-progress.md) voor de voortgangs- en beloningsgrens, [docs/trial-week-access.md](docs/trial-week-access.md) voor proefweek en rechten, [docs/taxi-mission.md](docs/taxi-mission.md) voor dag 2, [docs/restaurant-mission.md](docs/restaurant-mission.md) voor dag 3, [docs/health-mission.md](docs/health-mission.md) voor dag 5, [docs/content-studio-access.md](docs/content-studio-access.md) voor de beheertoegangslaag, [docs/content-foundation.md](docs/content-foundation.md) voor het contentfundament, [docs/content-studio-crud.md](docs/content-studio-crud.md) voor de beheerinterface, [docs/content-studio-review-workflow.md](docs/content-studio-review-workflow.md) voor de reviewworkflow, [docs/content-studio-release-workflow.md](docs/content-studio-release-workflow.md) voor releasebeheer, [docs/public-content-api.md](docs/public-content-api.md) voor het runtimecontract en [docs/quality-pipeline.md](docs/quality-pipeline.md) voor de automatische kwaliteitsgrens.

## Applicatiefundament

De applicatiebasis gebruikt Laravel 13 op PHP 8.4, MySQL, Vite en Tailwind CSS 4. De startpagina toont de status van de eerste vertical slice; de gezondheidscontrole is beschikbaar via `/up`.

Belangrijkste lokale controles:

```bash
npm run validate
npm run build
php artisan test
```

Voor Ploi-instellingen, omgevingsvariabelen en de deploymentvolgorde: zie [docs/deployment/ploi.md](docs/deployment/ploi.md).
