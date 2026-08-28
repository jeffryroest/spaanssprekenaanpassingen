# Fase 3B3 — gezondheidsmissie

Dag 5 maakt Consulta La Luz speelbaar als fictief taalrollenspel. De speler gebruikt uitsluitend een vaste rolkaart, legt Elena twee eenvoudige klachten uit, beantwoordt één niveaugebonden vervolgvraag, vraagt om schriftelijke uitleg en zoekt de apotheek. De missie vraagt nooit om echte gezondheidsgegevens en geeft geen medisch advies.

## Content en contract

- Content Studio-slug: `consulta-elena`.
- Missiesleutel: `mission.madrid.health.appointment`.
- NPC: `npc.elena.ortiz`.
- Contract: `docs/contracts/health-dialogue-v1.schema.json`.
- Voorbeeld voor redactionele invoer: `content/examples/health-dialogue-domain-data.json`.

Het voorbeeld is alleen een starter. Een redacteur maakt er een concept mee, een taalreviewer controleert de Spaanse en Nederlandse inhoud én de rolkaartgrens, en een hoofdredacteur publiceert een expliciete productierelease. De applicatie publiceert dit voorbeeld nooit automatisch.

De bijgewerkte Madrid-starter bevat de hotspot `madrid.consulta.luz`. Het runtime-readinessdashboard markeert de Madrid-wereld als onvolledig zolang de actieve productieversie deze locatie nog mist; zo blijft de route ook werkelijk vanuit de wereld vindbaar na een handmatige Madrid-review en -release.

## Fictieve rolkaart

De rolkaart bevat vier vooraf vastgelegde feiten: keelpijn, een droge hoest, sinds gisteren, geen koorts en normale ademhaling. Deze feiten zijn leercontent en geen informatie over de speler.

- De interface zegt vóór de eerste beurt dat alleen de rolkaart gebruikt mag worden.
- De taalbeoordelaar beoordeelt uitsluitend taal en taakuitvoering; het systeembericht verbiedt een medische beoordeling, diagnose of advies.
- De Content Studio-validator weigert `health_text_dialogue` zonder `roleplay.fictional: true` en minimaal vier rolkaartfeiten.
- De missie onthoudt geen gezondheidsfeit als NPC-geheugen of accountvoortgang.

## Vijf actieve beurten

1. volgens de rolkaart keelpijn en een droge hoest noemen;
2. zeggen dat dit sinds gisteren zo is;
3. een niveaugebonden controlevraag beantwoorden;
4. om schriftelijke uitleg vragen;
5. begrip bevestigen en naar de apotheek vragen.

A0 beantwoordt een korte koortsvraag, A1 beschrijft het soort hoest en A2 contrasteert slikpijn met normaal ademen. Voorbeeldzinnen blijven optionele steun; vrije tekst of spreken is de hoofdroute.

## Runtimegrenzen

| Route | Functie | Grens |
| --- | --- | --- |
| `/spelen/madrid/gezondheid` | fictief consultrollenspel | login + `trial_week` |
| `/spelen/madrid/gezondheid/content` | exacte productiepublicatie | login + recht + privé, niet cachebaar |
| `/spelen/madrid/gezondheid/transcriptie` | WebM/Opus naar Spaans transcript | login + recht + rate limit |
| `/spelen/madrid/gezondheid/feedback` | gelaagde taalfeedback | login + recht + scenarioslug |
| `/spelen/madrid/gezondheid/voltooien` | server-side voortgang | login + recht + rate limit |

`runtime_access.visibility: entitled` houdt de missie uit de openbare content-API. De accountgebonden contentroute controleert het recht en het actuele productiecontract opnieuw en antwoordt met `Cache-Control: private, no-store`.

## Privacy en lokale hervatting

De generieke dialoogmotor bewaart bij deze gevoelige missie alleen structureel hervatbewijs in `sessionStorage`: stap-id, invoerbron, hulpgebruik en tellers. Antwoordtekst, NPC-tekst en persoonlijke feedback worden vóór iedere lokale schrijfactie uit de opgeslagen kopie verwijderd. Tijdens de geopende pagina kan het actuele gesprek in het werkgeheugen en op het scherm zichtbaar zijn; na herladen toont de historie alleen dat de beurt is voltooid.

De server bewaart eveneens alleen gevalideerde stap-id's, invoerbron, hulpgebruik, generieke gespreksstates en beloningsmutaties. Audio, antwoorden, transcripties, feedback en gezondheidsinformatie komen niet in accountvoortgang.

## Beloningen en visuele ervaring

Een volledig zelfstandige route levert maximaal 150 XP, maximaal 3 Confianza en eenmaal 1 Valentía. Unieke beloningen zijn de stempel `Ik sprak in de spreekkamer`, de `Zinnenkaart voor de consulta`, een vooruitblik op de stationsmissie en optioneel de herstelbadge `Duidelijk doorvragen`.

De scène gebruikt warm saliegroen, daglicht, een plant, bureau en rolkaart. Alle essentiële informatie blijft semantische HTML. Toetsenbordbediening, tekstfallback, 200% zoom en `prefers-reduced-motion` blijven onderdeel van de acceptatiegrens.
