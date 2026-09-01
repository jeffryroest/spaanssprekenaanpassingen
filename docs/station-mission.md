# Fase 3B6 — stationsmissie

Dag 6 maakt Estación del Centro speelbaar als fictief taalrollenspel. De speler regelt met stationsmedewerker Mateo een kaartje naar Toledo, kiest een retour, reageert op een niveaugebonden wijziging, vraagt naar prijs en betaalwijze en controleert tijd en perron. De oefening toont geen actuele dienstregeling en maakt geen echte boeking.

## Content en contract

- Content Studio-slug: `estacion-mateo`.
- Missiesleutel: `mission.madrid.station.ticket`.
- NPC: `npc.mateo.alvarez`.
- Contract: `docs/contracts/station-dialogue-v1.schema.json`.
- Voorbeeld voor redactionele invoer: `content/examples/station-dialogue-domain-data.json`.
- Vereiste mediarollen: `scene_background` en `npc_expression_sheet`.

Het voorbeeld en de twee meegeleverde illustraties worden alleen als concept geïnstalleerd. Een redacteur controleert de oefenreis, Spaanse en Nederlandse inhoud, routevertakkingen en alternatieve tekst. Review en een expliciete productierelease blijven verplicht.

## Fictieve oefenreis

De auteurvriendelijke `journey`-sectie bevat een titel, waarschuwing en minimaal drie vertaalde reisdetails. Het contract vereist `fictional: true`. Daardoor kan de Content Studio dezelfde kaart tonen als de speler, zonder dat de dialoog een actuele vervoersdienst of boeking suggereert.

De voorbeeldroute gebruikt:

- bestemming Toledo;
- heenreis morgen in de ochtend;
- terugreis zondag.

Dit zijn uitsluitend leergegevens. Accountvoortgang bewaart geen bestemming, dag, tijd, prijs of betaalwijze.

## Vijf actieve beurten

1. een kaartje naar Toledo voor morgenochtend vragen;
2. een retour kiezen en zondag als terugreisdag noemen;
3. een niveaugebonden tijd- of verbindingswijziging oplossen;
4. prijs vragen en met de kaart willen betalen;
5. vertrektijd controleren, naar het perron vragen en bedanken.

A0 kiest uit twee tijden, A1 accepteert een latere trein en A2 vergelijkt een vroege verbinding met overstap met een latere rechtstreekse trein. Vrij spreken of typen blijft de hoofdroute; voorbeeldzinnen zijn optionele steun.

## Runtimegrenzen

| Route | Functie | Grens |
| --- | --- | --- |
| `/spelen/madrid/station` | visuele dag-6-missie | login + `trial_week` |
| `/spelen/madrid/station/content` | exacte productiepublicatie | login + recht + privé, niet cachebaar |
| `/spelen/madrid/station/media/{version}/{role}` | versiegebonden scène- en NPC-beeld | login + recht + privé, niet cachebaar |
| `/spelen/madrid/station/transcriptie` | WebM/Opus naar Spaans transcript | login + recht + rate limit |
| `/spelen/madrid/station/feedback` | gelaagde taalfeedback | login + recht + scenarioslug |
| `/spelen/madrid/station/voltooien` | server-side voortgang | login + recht + rate limit |

Audio, antwoorden, transcripties en AI-feedback worden niet in accountvoortgang opgeslagen. De server reconstrueert de actuele gepubliceerde route uit stap-id, invoerbron en hulpgebruik.

## Visuele ervaring en beloningen

De scène gebruikt een warme, hedendaagse stationshal met Mateo in drie reactiestaten: luisteren, uitleggen en het ticket overhandigen. Beide assets zijn versiegebonden aan de contentrevisie; de meegeleverde WebP-bestanden zijn uitsluitend veilige runtimefallbacks.

Een volledig zelfstandige route levert maximaal 160 XP, maximaal 3 Confianza en eenmaal 1 Valentía. Unieke beloningen zijn de stempel `Mijn eerste treinkaartje`, het geïllustreerde kaartje naar Toledo, de vooruitblik op de slotmissie en optioneel de herstelbadge `Reisvraag opgelost`.

## Acceptatiecriteria

- Alleen een ingelogde speler met `trial_week` kan dag 6 openen.
- De proefweek toont dag 6 pas als speelbaar na een geldige productiepublicatie; het readinessdashboard vereist beide gekoppelde mediarollen.
- De Content Studio bewerkt en previewt oefenreis, dialoog, niveauroutes en media zonder handmatige JSON-kennis.
- Alle drie niveaus vormen exact vijf servervalideerbare beurten.
- Herhalen of opnieuw verzenden levert nooit dubbele valuta of unieke beloningen op.
- Ontbrekende content of media toont een herstelbare toestand; de statische fallback draagt geen essentiële informatie.
- De interface blijft bruikbaar vanaf 360 pixels, met toetsenbord, schermlezer, 200% zoom en verminderde beweging.
- Voorbeeldcontent wordt nooit automatisch gereviewd of gepubliceerd.
