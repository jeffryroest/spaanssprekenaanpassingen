# Fase 3B2 — restaurantmissie

Dag 3 maakt Café El Reloj speelbaar als volledige restaurantmissie. De speler vraagt Carmen om een tafel, bestelt water zonder bubbels en eten, reageert op één niveaugebonden bedieningsvraag en vraagt tot slot om nog wat water en de rekening.

## Content en contract

- Content Studio-slug: `restaurant-el-reloj`.
- Missiesleutel: `mission.madrid.restaurant.order`.
- NPC: `npc.carmen.santos`.
- Contract: `docs/contracts/restaurant-dialogue-v1.schema.json`.
- Voorbeeld voor redactionele invoer: `content/examples/restaurant-dialogue-domain-data.json`.

Het voorbeeld is alleen een starter. Een redacteur maakt er een concept mee, een taalreviewer controleert de Spaanse en Nederlandse inhoud en een hoofdredacteur publiceert een expliciete productierelease. De applicatie publiceert deze content nooit automatisch.

## Vijf actieve beurten

1. begroeten en vragen of er een tafel voor twee vrij is;
2. water zonder bubbels bestellen;
3. een niveaugebonden vraag afhandelen;
4. tortilla en salade bestellen;
5. om nog wat water en de rekening vragen.

De niveauvarianten zijn bewust communicatief: A0 bevestigt `sin gas`, A1 kiest een alternatief wanneer de eerste drank niet beschikbaar is en A2 vraagt om een alcoholvrije aanbeveling. Voorbeeldzinnen zijn optionele steun; de speler kan altijd zelf typen of spreken.

## Runtimegrenzen

| Route | Functie | Grens |
| --- | --- | --- |
| `/spelen/madrid/restaurant` | speelbare restaurantscène | login + `trial_week` |
| `/spelen/madrid/restaurant/content` | exacte productiepublicatie | login + recht + privé, niet cachebaar |
| `/spelen/madrid/restaurant/transcriptie` | WebM/Opus naar Spaans transcript | login + recht + rate limit |
| `/spelen/madrid/restaurant/feedback` | gelaagde tekstfeedback | login + recht + scenarioslug |
| `/spelen/madrid/restaurant/voltooien` | server-side voortgang | login + recht + rate limit |

`runtime_access.visibility: entitled` houdt de missie uit de openbare content-API. De accountgebonden contentroute controleert het recht en het actuele productiecontract opnieuw en antwoordt met `Cache-Control: private, no-store`.

## Beloningen en privacy

Een volledig zelfstandige route levert maximaal 140 XP, maximaal 3 Confianza en eenmaal 1 Valentía. Unieke beloningen zijn de stempel `Mijn eerste diner`, de `Onderzetter van El Reloj`, een vooruitblik op de gezondheidsmissie en optioneel de herstelbadge `Met gemak doorpraten`.

Alleen missiestappen, invoerbron en hulpgebruik worden als bewijs opgeslagen. Audio, antwoorden, transcripties en feedback worden niet in accountvoortgang bewaard.

## Visuele en toegankelijke ervaring

De restaurantscène gebruikt warme avondkleuren, azulejos, een gedekte tafel en een duidelijk personagepaneel. De bestaande semantische dialoogstructuur, toetsenbordbediening, tekstfallback en `prefers-reduced-motion`-grens blijven behouden.
