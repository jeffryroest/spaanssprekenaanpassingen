# Fase 3D3A — operationeel betaaloverzicht

## Doel

Maak financiële uitzonderingen vindbaar voor een bevoegde beheerder zonder een nog niet vastgesteld refund-, chargeback- of achterstandsbeleid stilzwijgend toe te passen.

## Gerealiseerd

- Alleen de Content Studio-rol `beheerder` kan `/content-studio/betalingen` openen.
- Bestellingen zijn via een `POST`-zoekactie doorzoekbaar op besteller, e-mailadres en intern bestelnummer en filterbaar op betaalstatus; persoonsgegevens komen daardoor niet in URL-querystrings terecht.
- Het overzicht toont totalen voor bestellingen, bevestigde betalingen, abonnementen met lopende toegang en orders die aandacht vragen.
- Mislukte, geannuleerde, verlopen, terugbetaalde en teruggeboekte betalingen worden uit de gesaneerde eventinbox als aandachtspunt getoond.
- Een latere refund of chargeback van de eerste betaling wordt aan het al bestaande lokale abonnement gekoppeld.
- Het technische `failure_code` van de ordersnapshot krijgt uitsluitend een stabiele statuscode, nooit vrije providertekst.
- Kaart-, bank- en volledige providergegevens worden niet getoond of opgeslagen.

## Bewuste grens

Deze tussenstap verandert de toegang niet automatisch. Een mislukte terugkerende betaling verlengt de betaalde periode niet, maar zet de lokale status evenmin automatisch op `past_due`, `paused` of `expired`. Ook een refund of chargeback trekt reeds verleende toegang niet automatisch in. Dat gedrag volgt pas na een expliciet besluit over hersteltermijn, communicatie, retries en uitzonderingen.

## Acceptatiecriteria

- Niet-beheerders krijgen `403` op het betaaloverzicht.
- Zoeken en statusfilters lekken geen andere records.
- Beheerresponses zijn `private, no-store` en zoektermen met persoonsgegevens komen niet in de URL.
- Een financiële terugdraaiing krijgt het ontvangsttijdstip als operationeel incidentmoment.
- Een bekende refund of mislukte incasso blijft idempotent verwerkt en is aan de juiste order of het juiste abonnement te herleiden.
- Bestaande toegang wijzigt niet zolang het productbeleid niet is vastgesteld.

## Vervolgpoort

De producteigenaar stelt vast:

1. hoeveel dagen hersteltermijn een mislukte incasso krijgt;
2. wanneer toegang wordt gepauzeerd of beëindigd;
3. wat een volledige of gedeeltelijke refund met toegang doet;
4. hoe chargebacks worden behandeld;
5. welke btw-/factuurgegevens en bewaartermijnen noodzakelijk zijn.
