# Visuele gouden route Madrid → La Espiga

Fase 3B5 werkt één route van aankomst tot wereldreactie volledig uit. De speler ziet dezelfde warme, hedendaagse Madrid-stijl in de wereldkaart, de bakkerij en Lucía. Alle opdrachten, knoppen en feedback blijven echte HTML; beeld ondersteunt de ervaring maar draagt nooit de enige betekenis.

## Spelersreis

1. De speler arriveert in Madrid en verkent drie optionele details.
2. La Espiga opent een korte boodschappenkaart zonder verplicht script.
3. Een rustige scèneovergang brengt de speler naar de geïllustreerde bakkerij.
4. Lucía toont drie consistente staten: luisteren, aanmoedigen en succes.
5. Na vijf geslaagde beurten verschijnen een paspoortstempel, broodzak en accountbeloningen.
6. De terugkeer toont La Espiga als voltooid en maakt Café El Reloj zichtbaar als volgende bestemming.

De route blijft bruikbaar met tekstinvoer, zonder geluid, met toetsenbord en met verminderde beweging. Bij ontbrekende redactionele media gebruikt de frontend de meegeleverde, versiegebonden WebP-assets; ontbrekende of ongeldige leercontent geeft nog steeds een duidelijke lege toestand.

## Media- en publicatiegrens

Het demopakket koppelt drie assets aan conceptrevisies:

| Content | Rol | Asset |
|---|---|---|
| `madrid` | `map_background` | Madrid in de ochtend |
| `la-espiga-lucia` | `scene_background` | Interieur van La Espiga |
| `la-espiga-lucia` | `npc_expression_sheet` | Lucía · drie reacties |

De spelers-API adverteert alleen media die aan exact de actuele productiepublicatie hangen, publiceerbaar zijn, toegankelijkheidstekst hebben en werkelijk op de private disk bestaan. De stream-URL bevat de revisieversie en mediarol, lekt geen objectkey en faalt gesloten met 404. Accountgebonden content krijgt via de openbare media-endpoint geen toegang.

## Installatie

```bash
php artisan game:install-demo-content --actor=beheerder@example.com --dry-run
php artisan game:install-demo-content --actor=beheerder@example.com
```

Een bestaand, aantoonbaar ongewijzigd democoncept zonder media krijgt een nieuwe conceptrevisie. Zodra tekst of media handmatig afwijkt, stopt het volledige pakket met een conflict. Review, preview en `PUBLICEREN` blijven altijd bewuste Content Studio-stappen.
Het installatiecommando publiceert niets automatisch.

Voor oude, onvolledige placeholders zonder `scene`, media of releasekoppeling bestaat een afzonderlijke beheerdersroute. Controleer altijd eerst droog en bevestig een echte vervanging daarna expliciet:

```bash
php artisan game:install-demo-content --actor=beheerder@example.com --dry-run --replace-existing
php artisan game:install-demo-content --actor=beheerder@example.com --replace-existing --confirm=OVERSCHRIJVEN
```

De oude revisie en reviewhistorie blijven daarbij intact; de pakketinhoud wordt een nieuwe conceptrevisie en gaat nooit automatisch naar review of productie.

## Acceptatiecriteria

- De runtime gebruikt bij voorkeur media van de exacte gepubliceerde revisie.
- Lucía reageert zichtbaar op de beurtstatus; alle reactie-informatie blijft ook tekstueel beschikbaar.
- Voltooiing toont beloning en een zichtbare verandering in Madrid.
- Media met een verkeerde versie, rol, toegang, rechtenstatus of ontbrekend object zijn niet publiek opvraagbaar.
- De frontend bevat fallbacks en blijft vanaf 360 px, bij 200% zoom en met verminderde beweging bedienbaar.
- De productiebuild controleert de gouden-routemarkers en beide nieuwe beeldassets.
