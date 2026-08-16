# Fase 3B1 — taximissie

## Doel

Dag 2 maakt de bestaande proefweekroute werkelijk speelbaar met een taxirit door Madrid. De speler begroet Diego, noemt het Museo del Prado als bestemming, reageert op één niveaugebonden routevraag, vraagt naar de verwachte prijs en betaalt met verzoek om een bon.

De missie blijft gericht op actieve reproductie: vrije tekst of spreken is de hoofdroute. Voorbeeldzinnen zijn optionele hulp en worden als assistentie geregistreerd.

## Content- en publicatiegrens

- Content Studio-slug: `taxi-diego`.
- Missiesleutel: `mission.madrid.taxi.ride`.
- Contract: `docs/contracts/taxi-dialogue-v1.schema.json`.
- Voorbeeld voor redactionele invoer: `content/examples/taxi-dialogue-domain-data.json`.
- De proefweek toont dag 2 pas als startbaar wanneer exact dit gespreksscenario in een productierelease staat.
- De applicatie publiceert het voorbeeldbestand nooit automatisch.

## Routes en toegang

| Route | Functie | Grens |
|---|---|---|
| `/spelen/madrid/taxi` | speelbare missie | login + `trial_week` |
| `/spelen/madrid/taxi/content` | exacte productiepublicatie | login + recht + privé, niet cachebaar |
| `/spelen/madrid/taxi/transcriptie` | WebM/Opus naar Spaans transcript | login + recht + rate limit |
| `/spelen/madrid/taxi/feedback` | gelaagde tekstfeedback | login + recht + rate limit |
| `/spelen/madrid/taxi/voltooien` | server-side voortgang | login + recht + rate limit |

De dialoogclient en feedbackresolver zijn scenario-onafhankelijk gemaakt. Nieuwe missiedagen leveren daardoor hun eigen gepubliceerde slug en endpoints, zonder de gesprek-, opname- of feedbackmotor te kopiëren.

`runtime_access.visibility: entitled` houdt de taxi uit de openbare content-API. De accountgebonden contentroute controleert daarnaast het recht en het contract van de productiepublicatie en antwoordt met `Cache-Control: private, no-store`. De feedbackroutes accepteren alleen hun eigen scenarioslug.

## Beloning en privacy

Een zelfstandige route levert maximaal 130 XP, maximaal 3 Confianza en eenmaal 1 Valentía. Unieke beloningen zijn de stempel `Mijn eerste taxirit`, de `Madrileense taxibon`, de restaurantvooruitblik en optioneel de herstelbadge `Goede reiziger`.

De server bewaart alleen gevalideerde stap-id's, invoerbron, hulpgebruik, gespreksstates en beloningsmutaties. Antwoorden, transcripties, audio en AI-feedback worden niet in de voortgangstabellen opgeslagen.

## Vervolg

- **3B2:** restaurantmissie.
- **3B3:** gezondheidsmissie.
- **3B4:** stationsmissie.
- Persoonlijke herhaling en minimaal NPC-geheugen blijven fase 3C.
