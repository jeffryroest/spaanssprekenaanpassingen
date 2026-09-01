# Fase 3C1 — persoonlijke herhaling

Dag 4 is een dynamische herhalingssessie met maximaal vijf kaarten uit gesprekken die de speler zelf heeft voltooid. De server gebruikt alleen de exacte, gepubliceerde missieroute en structurele poginggegevens. Er wordt geen persoonlijke antwoordtekst gereconstrueerd of opgeslagen.

## Selectie

`PersonalReviewDeck` ondersteunt La Espiga, taxi, restaurant, consulta en station. Per missie telt alleen de nieuwste voltooide poging waarvan het scenario nog via een geldige productiepublicatie beschikbaar is. Kaarten worden in deze volgorde gekozen:

1. een eerder geplande kaart die nu vervallen is;
2. een nieuwe beurt waarbij hulp is gebruikt;
3. een nieuwe getypte beurt;
4. een nieuwe gesproken beurt.

Een nog niet vervallen kaart wordt niet getoond. Zijn alle kaarten bijgewerkt, dan krijgt de speler een rustige lege toestand met een route naar een andere missie.

## Intervalplanning

Na spreken of typen vergelijkt de speler de eigen poging met één gepubliceerd voorbeeld en kiest:

| Beoordeling | Eerste interval | Gedrag |
| --- | ---: | --- |
| Nog eens | 10 minuten | succesreeks terug naar nul, lapse +1 |
| Moeilijk | 1 dag | interval groeit daarna ×1,5 |
| Goed | 3 dagen | interval groeit daarna ×2,2 |
| Makkelijk | 7 dagen | interval groeit daarna ×3 |

De maximale intervallen zijn 60 dagen voor `hard`/`good` en 90 dagen voor `easy`. Dit is bewust een transparante, deterministische planner; er is geen oncontroleerbare AI-planning.

## Opslag- en beloningsgrens

`user_practice_items` is een snelle, vervangbare projectie met:

- gehashte kaart-id;
- bronmissie, contentnode, revisieversie en stap-id;
- interval, herhalingsaantallen, laatste beoordeling en volgende datum.

Elke sessie wordt append-only vastgelegd in `mission_attempts` onder `mission.madrid.review.personal`. Het bewijs bevat alleen kaart-id, bron, stap-id, invoerbron, hulpvlag, beoordeling en volgend interval. Audio, antwoordtekst, transcript en feedback ontbreken altijd.

Alle herhalingssessies op één kalenderdag leveren samen maximaal 20 XP. Minstens drie gesproken kaarten in een sessie leveren maximaal eenmaal per dag 1 Confianza. Nieuwe sessies op dezelfde dag blijven de planning bijwerken, maar de server boekt alleen het nog niet verdiende deel van het dagmaximum en gebruikt per sessie unieke ledger-idempotencykeys.

## Routes

| Route | Functie | Grens |
| --- | --- | --- |
| `/spelen/madrid/herhaling` | visuele dag-4-sessie | login + `trial_week` |
| `/spelen/madrid/herhaling/deck` | persoonlijk deck v1 | login + recht + privé, niet cachebaar |
| `/spelen/madrid/herhaling/transcriptie` | vluchtige WebM/Opus-transcriptie | login + recht + rate limit |
| `/spelen/madrid/herhaling/voltooien` | intervalplanning en dagbeloning | login + recht + idempotent |

Het responscontract staat in `docs/contracts/personal-review-v1.schema.json`.

## Acceptatiecriteria

- Zonder `trial_week` zijn alle dag-4-routes server-side geblokkeerd.
- Zonder voltooide missie verschijnt geen verzonnen oefenkaart.
- Maximaal vijf kaarten komen uitsluitend uit werkelijk gespeelde, nog gepubliceerde routes.
- Hulpgebruik en getypte beurten krijgen voorrang op zelfstandige gesproken beurten.
- Antwoordtekst, audio, transcript en AI-feedback komen niet in request, projectie, poging of ledger.
- Dezelfde voltooiingssleutel en meerdere sessies op dezelfde dag leveren nooit dubbele valuta op.
- De sessie werkt met toetsenbord, schermlezer, 200% zoom, verminderde beweging en vanaf 360 pixels.
- Dag 4 vraagt geen Content Studio-publicatie, omdat uitsluitend reeds gereviewde broncontent wordt samengesteld.
