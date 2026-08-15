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

Zie [AGENTS.md](AGENTS.md) voor de samenwerking, [docs/roadmap.md](docs/roadmap.md) voor de ontwikkelvolgorde, [docs/content-studio-access.md](docs/content-studio-access.md) voor de toegangslaag, [docs/content-foundation.md](docs/content-foundation.md) voor het contentfundament, [docs/content-studio-crud.md](docs/content-studio-crud.md) voor de beheerinterface, [docs/content-studio-review-workflow.md](docs/content-studio-review-workflow.md) voor de reviewworkflow, [docs/content-studio-release-workflow.md](docs/content-studio-release-workflow.md) voor releasebeheer en [docs/public-content-api.md](docs/public-content-api.md) voor het runtimecontract.

## Applicatiefundament

De applicatiebasis gebruikt Laravel 13 op PHP 8.4, MySQL, Vite en Tailwind CSS 4. De startpagina toont de status van de eerste vertical slice; de gezondheidscontrole is beschikbaar via `/up`.

Belangrijkste lokale controles:

```bash
npm run validate
npm run build
php artisan test
```

Voor Ploi-instellingen, omgevingsvariabelen en de deploymentvolgorde: zie [docs/deployment/ploi.md](docs/deployment/ploi.md).
