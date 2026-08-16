# Roadmap

## Fase 0 — fundament

- Agenthandboek en beslislogboek.
- Canoniek domein- en datamodel.
- Content Studio- en importworkflow.
- Specificatie en seedcontent voor Madrid → La panadería.
- Keuze en vastlegging van uitvoeringsomgeving.

**Beslispoort:** architectuur en vertical slice zijn intern consistent.

## Fase 1 — skeletapplicatie

- Laravel-applicatie met authenticatie en rollen.
- MySQL-migraties en seeders.
- Content Studio-navigatie en basis-CRUD.
- Gedeeld Content Studio-designsystem en responsieve beheerschil.
- Versiegebonden reviewworkflow met vier-ogencontrole.
- Versiegebonden releaseworkflow met kanaalpreflight en expliciete productiebevestiging.
- Versieerbare read-only wereld-, locatie-, missie- en conversatie-API met productiepublicatiegrens.
- GitHub Actions-test- en kwaliteitsstraat voor PHP, frontend, API-contracten en MySQL-integratie.

**Beslispoort:** content kan veilig als concept worden aangemaakt, bekeken en gepubliceerd.

## Fase 2 — speelbare Madrid-slice

- **2A — Visuele Madrid-hub:** productie-API-gedreven buurtkaart, vier hotspots, drie onderzoekspunten en toegankelijke lijstweergave.
- **2B — La panadería:** betreedbare locatie, Lucía en een hervatbare, vertakkende tekstdialoog.
- **2C — Spreken en transcriptie:** expliciete microfoontoestemming, WebM/Opus van maximaal 12 seconden, lokaal terugluisteren, veilige Spaanse transcriptie en tekstfallback.
- **2D — Gelaagde feedback:** servergevalideerde rubricfeedback op het transcript, communicatief succes eerst, één concrete taal-/gespreksfocus en veilige herkansing; uitspraak blijft zonder audio-evidence expliciet onbeoordeeld.
- **2E — Accountvoortgang:** duurzame voortgang, Confianza, Valentía, spreekdoel en eerste beloning.

**Beslispoort:** een nieuwe gebruiker kan de kernlus zonder hulp voltooien.

## Fase 3 — proefweek

- Zeven missiedagen.
- Taxi, restaurant, gezondheid en station.
- Persoonlijke herhaling en NPC-geheugen.
- Proefperiode, paywall en abonnement.

**Beslispoort:** retentie, spreken en conversie zijn meetbaar.

## Fase 4 — bèta en uitbreiding

- Beveiligings- en privacyreview.
- Beheer, support en analytics.
- Gesloten bèta.
- Nieuwe Spaanse steden als contentpakketten.
