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
- **2E — Accountvoortgang (gerealiseerd):** productiegebonden routevalidatie, duurzame voortgang, idempotente XP/Confianza/Valentía, spreekdoel, accountdashboard en eerste unieke beloningen.

**Beslispoort:** een nieuwe gebruiker kan de kernlus zonder hulp voltooien.

## Fase 3 — proefweek

- **3A — proefweek en toegangsgrenzen (gerealiseerd):** zeven-dagenroute, abonnementsprojectie, centrale rechtenservice, server-side middleware en versieerbaar toegangscontract; geen prijs of betaalactivatie zonder productbesluit.
- **3B1 — taxi (gerealiseerd):** vijf actieve beurten met Diego, drie niveaupaden, spreken/tekst, productiecontent en duurzame accountbeloningen.
- **3B1.5 — speelbare frontend en visuele wereldlaag (gerealiseerd):** spelersgerichte startpagina, geïllustreerde Madrid-wereld, aankomst en missievoorbereiding, zichtbare wereldreactie, Content Studio-starters en productie-assetcontrole.
- **3B2 — restaurant (gerealiseerd):** vijf actieve beurten met Carmen in Café El Reloj, drie niveaupaden, spreken/tekst, visuele restaurantscène, productiecontent en duurzame accountbeloningen.
- **3B2.5 — demo-ready Content Studio (gerealiseerd):** risicogestuurde zelfgoedkeuring voor gewone content, onafhankelijke review voor gevoelige content, veilige reviewintrekking, inhoudelijke speelbaarheidscontrole en een idempotent conceptpakket; fase 3B6 breidt dit uit naar zes voorbeeldrecords.
- **3B3 — gezondheid (gerealiseerd):** fictieve rolkaart, vijf actieve beurten met Elena, drie niveaupaden, gevoelige lokale tekstredactie, taalfeedback zonder medische beoordeling en duurzame dag-5-beloningen.
- **3B4 — speelcontentbouwer en preview (gerealiseerd):** typespecifieke wereld-, NPC- en gesprekseditor, diepe routevalidatie, privémediabeheer en een voortgangsvrije spelpreview.
- **3B5 — visuele gouden route (gerealiseerd):** gereviewde scène- en personage-assets via de runtime, drie zichtbare Lucía-reacties, tastbare beloningspresentatie, veranderde Madrid-hub en één volledig geteste Madrid → La Espiga-spelersreis.
- **3B5.1 — veilige productieactivatie (gerealiseerd):** expliciete reconciliatie van onvolledige, nooit gepubliceerde demoplaceholders naar een nieuwe conceptrevisie, met behoud van revisie-, review- en auditgeschiedenis en een harde blokkade voor echte of releasegebonden content.
- **3B6 — station (gerealiseerd):** vijf actieve beurten met Nuria aan de kaartverkoop van Atocha, drie niveaupaden, spreken/tekst, visuele stationsscène, productiecontent en duurzame dag-6-beloningen.
- **3C — persoonlijke continuïteit:** gespreide herhaling en minimaal, spelrelevant NPC-geheugen.
- **3D — conversie:** proefactivatie, paywall, provider-events en abonnement na expliciete besluiten over prijs en voorwaarden.

**Beslispoort:** retentie, spreken en conversie zijn meetbaar.

## Fase 4 — bèta en uitbreiding

- Beveiligings- en privacyreview.
- Beheer, support en analytics.
- Gesloten bèta.
- Nieuwe Spaanse steden als contentpakketten.
