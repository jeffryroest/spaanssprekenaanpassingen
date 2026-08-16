# Accountvoortgang v1

Fase 2E maakt het resultaat van `mission.madrid.panaderia.breakfast` duurzaam voor ingelogde spelers. La Espiga blijft zonder account speelbaar; alleen accountopslag, accountbalansen en blijvende ontgrendelingen vereisen authenticatie.

## Vertrouwensgrens

De browser stuurt via `POST /spelen/madrid/la-panaderia/voltooien` uitsluitend een UUID, niveau en vijf minimale beurtbewijzen: `step_id`, invoerbron en hulpgebruik. Het contract staat in [`contracts/panaderia-completion-v1.schema.json`](contracts/panaderia-completion-v1.schema.json).

De server:

1. laadt exact de actuele productiepublicatie van `la-espiga-lucia`;
2. reconstrueert de toegestane route voor A0, A1 of A2;
3. accepteert alleen exact vijf stappen in de juiste volgorde;
4. berekent 80–120 XP, 0–3 Confianza en de eerste Valentía zelf;
5. schrijft ledger, poging, projectie en unieke beloningen in één transactie;
6. geeft dezelfde `completion_key` zonder tweede mutatie terug.

Antwoorden, transcripties, audio, rubricfeedback, door de client berekende valuta en clientstates zijn verboden in dit opslagverzoek. De huidige invoerbron en herstelclaim zijn minimale clientattestaties; cryptografisch gekoppelde beurtbewijzen horen bij een latere server-side gespreksregie en zijn geen stilzwijgende aanname in deze fase.

## Persistentiemodel

| Tabel | Functie | Mutatiebeleid |
|---|---|---|
| `user_game_states` | snelle projectie voor XP, Confianza en Valentía | uitsluitend binnen beloningstransactie |
| `user_mission_progress` | beste score, spreekdoel en actuele wereldstates | projectie per gebruiker en missie |
| `mission_attempts` | controleerbare voltooihistorie plus minimale route-evidence | append-only |
| `game_ledger` | iedere valutamutatie en saldo erna | append-only, unieke idempotency key |
| `user_rewards` | stempel, verzamelitem, badge en ontgrendelingen | onveranderlijk, uniek per reward key |

De canonieke architectuur voorziet later typespecifieke `missions`, `mission_steps`, `item_definitions` en `user_inventory`. Die tabellen zijn nog niet gemigreerd. Deze vertical slice verwijst daarom aantoonbaar naar de gebruikte gepubliceerde `conversation_scenario` en versie. `user_rewards` is de tijdelijke runtimeprojectie voor zowel items als niet-itembeloningen; de latere typespecifieke migratie moet de sleutels behouden en backfillbaar zijn.

## Beloningsregels

- Basis: 80 XP; iedere zelfstandige beurt voegt 8 XP toe, met een maximum van 120.
- Confianza: maximaal drie, uitsluitend voor als `speech` gemarkeerde voltooide beurten.
- Valentía: één bij de eerste geldige voltooiing.
- Een betere latere poging boekt alleen het verschil met de eerdere beste score.
- Stempel, broodzak, vrije oefening en cafévooruitblik zijn uniek per account.
- `Sin miedo` wordt eenmaal toegevoegd wanneer de speler in de dialoog een herstelstrategie heeft gebruikt.

## Fout- en hervatgedrag

- De browser toont eerst de lokale missie-uitkomst; een trage opslag blokkeert de afronding niet.
- Bij uitval blijft dezelfde UUID in `sessionStorage`; **Opnieuw opslaan** is daardoor veilig.
- Een gast krijgt een loginroute die alleen naar de bekende La Espiga-route mag terugwijzen. Na login hervat de sessie en synchroniseert de voltooide missie.
- Een gewijzigde of ontbrekende productiepublicatie geeft een stabiele foutcode zonder gedeeltelijke boeking.

## Privacy

Accountopslag bevat stap-id's, bron (`speech`, `typed_assist`, `choice_assist`), hulpindicator, afgeleide states, valuta en beloningssleutels. Ruwe audio, antwoordtekst, transcript, confidence, correcties en AI-feedback worden niet in deze voortgangstabellen opgeslagen of teruggegeven.

## Acceptatiecriteria

- geldige A0-, A1- en A2-routes worden tegen productiecontent gevalideerd;
- een ongeldige route schrijft geen poging of ledgerregel;
- dezelfde completion UUID muteert nooit tweemaal;
- een betere replay topte alleen XP/Confianza op;
- unieke beloningen worden niet gedupliceerd;
- voortgang blijft zichtbaar na uitloggen en opnieuw inloggen;
- gasten kunnen spelen maar niet naar een account schrijven;
- route-, contract-, Laravel-, build- en MySQL-controles slagen.
