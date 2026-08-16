# Madrid-hub v1

Fase 2A levert de eerste runtime-interface van de speelbare Madrid-slice. De route `/spelen/madrid` bevat een responsieve buurtkaart, vier locaties, drie onderzoekspunten, een semantische lijstweergave en een lokale `Curiosidad`-teller.

## Productiegrens

De Blade-view bevat uitsluitend de spelinterface. Alle actieve titel-, opdracht-, hotspot- en onderzoeksteksten worden opgehaald via `GET /api/v1/worlds/madrid?locale=nl-NL`. Daardoor blijft de in fase 1F vastgelegde regel intact: alleen de actuele revisie uit een uitgevoerde productierelease wordt speelbaar.

Een ontbrekende, ongeldige of niet-gepubliceerde wereld toont een duidelijke lege toestand. `content/examples/madrid-hub-domain-data.json` is alleen een invoervoorbeeld voor `domain_data`; de runtime leest dit bestand nooit rechtstreeks.

## Content Studio

Maak in de Content Studio een contentobject aan met:

- type: `region`;
- slug: `madrid`;
- standaardlocale: bij voorkeur `nl-NL`;
- `domain_data`: de structuur uit `content/examples/madrid-hub-domain-data.json`;
- contractversie: `1.0.0`.

Laat het object daarna beoordelen en neem exact de goedgekeurde versie op in een productierelease. De kaart blijft gesloten tot die release werkelijk is gepubliceerd.

## Interactie en toegankelijkheid

- Alle hotspots en onderzoekspunten zijn echte knoppen met een beschrijvend label.
- De lijstweergave is een volledig bedienbaar alternatief voor de visuele kaart.
- Informatieve panelen sluiten met de sluitknop of Escape.
- Spaanse tekst gebruikt `lang="es"` en wordt gekoppeld aan Nederlandse ondersteuning.
- Statuswijzigingen gebruiken één bescheiden `aria-live`-regio.
- De bestaande `prefers-reduced-motion`-regel schakelt decoratieve animaties vrijwel uit.
- De kaart bevat geen tijdsdruk en geluid staat standaard uit.

## Bewuste afbakening

- De knop van La Espiga opent de betreedbare bakkerijroute uit fase 2B; de hub blijft verantwoordelijk voor ontdekking en navigatie.
- `Curiosidad` wordt voorlopig alleen in `sessionStorage` bewaard en is nog geen accountvoortgang.
- Er worden nog geen audiofiles geladen of analyticsgebeurtenissen verstuurd.
- De overige drie locaties blijven vergrendelde vooruitblikken.

## Acceptatiecriteria

- `/spelen/madrid` is publiek en mobiel bruikbaar;
- precies één van minimaal vier hotspots is open;
- minimaal drie onderzoekspunten leveren elk eenmaal één `Curiosidad` op;
- niet-gepubliceerde of contractongeldige data faalt gesloten met een herstelbare foutstatus;
- kaart- en lijstbediening werken met toetsenbord;
- voorbeeldcontent omzeilt de publieke productie-API niet;
- feature-, contract- en productiebuildcontroles slagen.
