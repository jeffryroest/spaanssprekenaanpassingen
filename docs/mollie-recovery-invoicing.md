# Fase 3D3B — betaalherstel en btw-vrijgestelde facturatie

## Geaccepteerde productkeuzes

- Een definitief mislukte, geannuleerde of verlopen maandincasso zet het abonnement op `past_due` en start één hersteltermijn van veertien dagen.
- De klant krijgt een bericht bij de start, na zeven dagen en op dag dertien. Een geslaagde betaling annuleert alle nog niet verstuurde herstelberichten.
- De herstellink opent na inloggen de actuele abonnementsstatus en schrijft nooit door een `GET`-verzoek geld af. Mollie voert provider-side retries uit; de applicatie maakt geen concurrerende incasso aan.
- Na het einde van de hersteltermijn vervalt extra toegang server-side. Het abonnement blijft operationeel zichtbaar totdat support of een volgende geverifieerde providerstatus het oplost.
- Een refund of chargeback pauzeert de lokale toegang direct en blijft als beheerincident zichtbaar. Een nieuwe checkout is geblokkeerd zolang deze controle openstaat.
- Opzeggen stopt verlenging en houdt toegang in stand tot het betaalde periode-einde. De klant kan dit zelf; een beheerder kan dit namens de klant uitvoeren.
- Het aanbod kost € 9,95 per maand en is door de producteigenaar aangemerkt als btw-vrijgesteld taalonderwijs.

## Facturatie

De factuurafzender wordt volledig uit afgeschermde servervariabelen opgebouwd. Juridische naam, vestigingsadres, KvK-nummer en btw-id staan bewust niet in Git, logs of browserpayloads. De publieke handelsnaam en het supportadres mogen als veilige standaard worden gebruikt.

Elke geverifieerde betaalde providergebeurtenis krijgt exact één factuur. Het nummer gebruikt een transactiegebonden jaarreeks in de vorm `SS-2026-000001`. De factuur bewaart onveranderlijke snapshots van afzender, afnemer, regel, bedrag en fiscale behandeling. De pdf vermeldt `Vrijgesteld van btw wegens taalonderwijs` en is alleen na authenticatie door de eigenaar te downloaden. De betaalbevestiging bevat dezelfde pdf als bijlage.

Particulieren geven voornaam, achternaam en e-mailadres op. Na de expliciete keuze `Bedrijf` worden ook bedrijfsnaam, btw-id en volledig factuuradres verplicht. Deze velden blijven buiten de webhookinbox, logs en spelers-API.

## Betrouwbare e-mailplanning

Betaalberichten worden eerst idempotent in `billing_email_deliveries` gepland. `billing:send-due-emails` verstuurt uitsluitend vervallen, niet-geannuleerde records, maximaal vijf pogingen per bericht. Foutmeldingen worden tot een stabiele code beperkt en bevatten geen e-mailadres of providerpayload.

De scheduler moet iedere vijftien minuten draaien via Laravel `schedule:run`. Zonder werkende mailtransportconfiguratie blijven betalingen en toegangsprojectie correct; alleen de communicatie wacht op herstel.

## Bewuste grenzen

- Live Mollie-checkout blijft standaard uit.
- De applicatie start geen handmatige recurring incasso naast Mollies eigen retries, om dubbele afschrijving te voorkomen.
- Refunds krijgen in deze fase geen automatische creditnota; zij blijven voor handmatige financiële controle zichtbaar.
- De juridische bewaartermijn is nog niet definitief geaccepteerd. Er is daarom geen automatische verwijdertaak voor facturen of besteladministratie toegevoegd.
- De onbekende OSS-status leidt niet tot landafhankelijke btw-logica zolang de vastgelegde behandeling `exempt` is; dit blijft onderdeel van de juridische productiecheck.

## Acceptatiecriteria

- De checkout bewaart zakelijke factuurvelden uitsluitend na een zakelijke keuze.
- Een betaald event maakt idempotent één opeenvolgend genummerde factuur en één bevestigingsbericht.
- Alleen de eigenaar kan de pdf downloaden; de response gebruikt `private, no-store`.
- Een terminale mislukte maandbetaling geeft precies veertien dagen toegang en plant dag 0, 7 en 13.
- Een herstelbetaling activeert het abonnement, verlengt de periode en annuleert openstaande herinneringen.
- Refund en chargeback blokkeren onmiddellijk lokale toegang.
- Klant en beheerder kunnen opzeggen zonder toegang vóór het betaalde periode-einde in te trekken.

Bron voor providergedrag: [Mollie recurring payments](https://docs.mollie.com/docs/recurring-payments). Mollie beschrijft dat mislukte abonnementsbetalingen afhankelijk van reden en betaalmethode maximaal vijf keer opnieuw kunnen worden geprobeerd.
