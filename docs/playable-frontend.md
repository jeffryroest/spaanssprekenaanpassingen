# Fase 3B1.5 — Speelbare frontend en visuele wereldlaag

Status: gerealiseerd op de fasebranch  
Datum: 2026-08-16

## Doel

Deze tussenfase maakt de bestaande technische vertical slice herkenbaar speelbaar voordat nieuwe missiedagen worden toegevoegd. De route is:

1. spelersgerichte startpagina;
2. korte aankomst bij Metro Sol;
3. actuele Madrid-doelkaart en verkenning;
4. boodschappenkaart met producten uit gepubliceerde wereldcontent;
5. gesprek met Lucía via spreken of tekst;
6. beloning en zichtbare ontgrendeling in de hub.

De taxi, het restaurant, het fictieve gezondheidsrollenspel en de stationsmissie gebruiken inmiddels deze afgeschermde spelerslaag. De slotmissie en persoonlijke herhaling blijven de volgende uitbreidingen.

## Content Studio

De contenteditor bevat nu een versiegebonden veld `domain_data`. Ingevoerde JSON wordt altijd syntactisch gecontroleerd. De bekende contracten `madrid_hub`, `panaderia_text_dialogue`, `taxi_text_dialogue`, `restaurant_text_dialogue`, `health_text_dialogue` en `station_text_dialogue` krijgen extra structuurvalidatie.

Zes startertemplates vullen een nieuw concept in:

| Starter | Contenttype | Slug | Runtimegrens |
|---|---|---|---|
| Madrid-wereld | Regio | `madrid` | publiek |
| La Espiga-gesprek | Gespreksscenario | `la-espiga-lucia` | publiek |
| Taxigesprek | Gespreksscenario | `taxi-diego` | recht `trial_week` |
| Restaurantgesprek | Gespreksscenario | `restaurant-el-reloj` | recht `trial_week` |
| Consultrollenspel | Gespreksscenario | `consulta-elena` | recht `trial_week` + fictieve rolkaart |
| Stationsgesprek | Gespreksscenario | `estacion-mateo` | recht `trial_week` + fictieve oefenreis |

Een starter schrijft niets naar de database totdat een editor het formulier opslaat. Opslaan maakt alleen een conceptrevisie. De bestaande vier-ogenreview, releasepreflight en expliciete productiebevestiging blijven ongewijzigd.

Het dashboard toont per vereist runtimecontract of het ontbreekt, in concept/review staat of werkelijk via een geldige productierelease speelbaar is.

## Visuele en interactieve laag

- De startpagina gebruikt de spelerstaal en één primaire actie: `Start je eerste missie`.
- De Madrid-hub gebruikt een geoptimaliseerde WebP-wereldillustratie van 242 KB; hotspots en tekst blijven semantische HTML.
- De aankomstoverlay verschijnt één keer per browsersessie en is met toetsenbord of Escape te sluiten.
- De boodschappenkaart wordt samengesteld uit gepubliceerde `inspectables` en tijdelijk in `sessionStorage` bewaard.
- La Espiga toont de keuze als geheugensteun, maar staat een andere vrije formulering toe.
- Na lokale of accountgebonden voltooiing toont La Espiga `Voltooid` en wordt Café El Reloj een zichtbare vooruitblik.
- Consulta La Luz toont alleen gepubliceerde rolkaartcontent en schrijft bij lokale hervatting geen antwoordtekst naar `sessionStorage`.
- Voorbeeldzinnen en opnameprivacy zijn inklapbare hulp, zodat spreken en vrije tekst de primaire acties blijven.
- Straatambiance start alleen na een expliciete klik en bevat geen essentiële informatie.

## Productiecontrole

`scripts/deploy-production.sh` gebruikt de beoordeelde lockbestanden, bouwt Vite-assets en stopt wanneer de fase-markers niet in de resulterende CSS/JavaScript staan. `scripts/smoke-live-frontend.mjs` controleert na deploy of het domein werkelijk de actuele bundels serveert.

## Acceptatiecriteria

- Een gast kan vanaf `/` via Madrid de volledige La Espiga-dialoog bereiken en afronden.
- Een gebruiker met `trial_week`-recht kan de taximissie blijven spelen.
- Een gebruiker met `trial_week`-recht kan dag 3 starten zodra `restaurant-el-reloj` is gereviewd en in productie gepubliceerd.
- Een gebruiker met `trial_week`-recht kan dag 5 starten zodra `consulta-elena` is gereviewd en in productie gepubliceerd.
- Een gebruiker met `trial_week`-recht kan dag 6 starten zodra `estacion-mateo` met beide vereiste mediarollen is gereviewd en in productie gepubliceerd.
- Ontbrekende productiecontent toont een herstelbare fout en nooit een eindeloze laadstatus.
- De publieke pagina’s bevatten geen frameworks of fasenummers.
- Interactie is toetsenbordbedienbaar, heeft tekstalternatieven en respecteert reduced motion.
- De kerninterface blijft bruikbaar vanaf 360 pixels en bij 200% zoom.
- De productiebuild wordt vóór cache-optimalisatie gecontroleerd.
- Geen startertemplate of codepad publiceert leercontent automatisch.
