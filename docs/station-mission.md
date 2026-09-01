# Fase 3B6 — stationmissie

Dag 6 maakt de kaartverkoop van Estación de Atocha speelbaar als volledige treinmissie. De speler vraagt Nuria om een retourticket naar Toledo voor de volgende dag, kiest een vertrektijd, reageert op één niveaugebonden wijziging, controleert prijs en spoor en rondt de aankoop af.

## Redactionele uitgangspunten

- Content Studio-slug: `station-nuria`.
- Missiesleutel: `mission.madrid.station.ticket`.
- Werk-NPC: `npc.nuria.martin`; naam en teksten blijven vóór publicatie redactioneel aanpasbaar.
- Contract: `docs/contracts/station-dialogue-v1.schema.json`.
- Voorbeeld: `content/examples/station-dialogue-domain-data.json`.

De voorbeeldinhoud is uitsluitend een conceptstarter. Een redacteur controleert de gesprekstaak en Spaanse formuleringen; review en productiepublicatie blijven expliciete handelingen. De demo-installer publiceert niets automatisch.

## Vijf actieve beurten

1. een retourticket naar Toledo voor morgen vragen;
2. de trein van negen uur kiezen;
3. een niveaugebonden wijziging of controlevraag afhandelen;
4. prijs en vertrekspoor vragen;
5. met kaart betalen en beide tickets meenemen.

A0 bevestigt alleen `ida y vuelta`. A1 reageert op een volle trein en accepteert of onderzoekt een latere optie. A2 controleert de voorwaarden van een flexibel tarief. Vrije tekst of spreken blijft de hoofdroute; voorbeeldzinnen zijn optionele hulp.

## Runtimegrenzen

| Route | Functie | Grens |
| --- | --- | --- |
| `/spelen/madrid/station` | speelbare stationsscène | login + `trial_week` |
| `/spelen/madrid/station/content` | exacte productiepublicatie | login + recht + privé, niet cachebaar |
| `/spelen/madrid/station/transcriptie` | WebM/Opus naar Spaans transcript | login + recht + rate limit |
| `/spelen/madrid/station/feedback` | gelaagde tekstfeedback | login + recht + scenarioslug |
| `/spelen/madrid/station/voltooien` | server-side voortgang | login + recht + rate limit |

`runtime_access.visibility: entitled` houdt de missie buiten de openbare content-API. Audio, antwoorden, transcripties en AI-feedback worden niet in accountvoortgang opgeslagen.

## Beloningen en visuele ervaring

Een volledig zelfstandige route levert maximaal 160 XP, maximaal 3 Confianza en eenmaal 1 Valentía. De unieke beloningen zijn de stempel `Mijn eerste treinkaartje`, het `Retourticket naar Toledo`, een vooruitblik op de slotmissie en optioneel de herstelbadge `Oplettende reiziger`.

De stationsscène gebruikt de bestaande warme Madrid-wereld, aangevuld met een lichte stationshal, vertrekbord, stationsklok, kaartbalie en trein. Alle essentiële informatie blijft echte HTML; toetsenbordbediening, tekstfallback, 200% zoom en `prefers-reduced-motion` blijven onderdeel van de acceptatiegrens.
