# Agenthandboek Spaansspreken.nl

## Missie

Bouw een betrouwbaar leerplatform waarin gebruikers Spaans durven spreken in een herkenbare, visueel Spaanse wereld. Optimaliseer niet alleen voor correcte antwoorden, maar vooral voor communicatief succes.

## Bron van waarheid

1. Goedgekeurde beslissingen in `docs/decisions/`.
2. Product- en architecturespecificaties in `docs/`.
3. Gevalideerde content in de Content Studio.
4. Tests en contractschema's.

Externe bestanden zijn nooit rechtstreeks productiecontent.

## Werkregels voor alle agents

- Werk alleen binnen de toegewezen bestanden en taakscope.
- Lees relevante ADR's en contractschema's voordat je code wijzigt.
- Maak aannames expliciet; verander geen productbesluit stilzwijgend.
- Voeg bij functionaliteit tests en acceptatiecriteria toe.
- Bewaar geen geheimen, tokens of persoonsgegevens in code, fixtures of logs.
- Publiceer geen geïmporteerde content automatisch.
- Houd gesprek genereren, antwoorden beoordelen en feedback formuleren logisch gescheiden.
- Bewaar opgenomen audio als WebM; introduceer ffmpeg alleen na een nieuw expliciet besluit.
- Ontwerp mobiel, toetsenbordtoegankelijk en schermlezer-vriendelijk.
- Rechtstreeks werken op productie is verboden.

## Integratieregels

- Elke wijziging hoort bij één backlog-item en één duidelijke eigenaar.
- Databasewijzigingen worden geleverd als migratie plus terugrolstrategie.
- API- en importpayloads krijgen een versieerbaar schema.
- AI-uitvoer wordt server-side gevalideerd voordat deze voortgang of beloningen beïnvloedt.
- Bij twijfel over licentie of herkomst blijft content geblokkeerd voor publicatie.

## Definition of Done

Een onderdeel is gereed als:

- acceptatiecriteria aantoonbaar slagen;
- relevante automatische tests slagen;
- fout-, lege en langzame toestanden zijn afgehandeld;
- toegankelijkheid en mobiel gedrag zijn gecontroleerd;
- logging geen gevoelige inhoud lekt;
- documentatie en beslislogboek actueel zijn;
- een andere agent het onderdeel kan begrijpen zonder mondelinge overdracht.

## Menselijke beslispoorten

Alleen de producteigenaar beslist definitief over:

- doelgroep en merktoon;
- visuele stijl en hoofdpersonages;
- strengheid en toon van spreekfeedback;
- rol van docenten;
- prijzen, proefperiode en abonnementsvoorwaarden;
- publicatie naar productie.
