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

Zie [AGENTS.md](AGENTS.md) voor de samenwerking en [docs/roadmap.md](docs/roadmap.md) voor de ontwikkelvolgorde.
