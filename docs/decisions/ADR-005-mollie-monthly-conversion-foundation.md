# ADR-005 · Mollie-maandaanbod en veilige conversiegrens

Status: gedeeltelijk geaccepteerd

## Geaccepteerde besluiten

- Betaalprovider: Mollie.
- Consumentenaanbod: € 9,95 per maand (`EUR 995` in minor units).
- De bestaande proefweek duurt zeven dagen.
- Na verlopen toegang blijft alleen de openbare voorbeeldmissie beschikbaar.

## Technisch besluit

Het commerciële plan staat versiegebonden in configuratie en wordt uitsluitend via een bewust, idempotent beheercommando in de database geplaatst. Proefactivatie en Mollie-verkeer hebben afzonderlijke, standaard uitgeschakelde omgevingsschakelaars.

De klassieke Mollie-webhook wordt niet vertrouwd als financiële statusbron. De callback bevat alleen een betalings-ID. De backend haalt de actuele betaling met de server-side API-sleutel bij Mollie op en bewaart alleen een gesaneerde status-snapshot in de bestaande `subscription_events`-inbox. Herhaalde callbacks met dezelfde financiële toestand leveren één event op; een latere refund of chargeback blijft een afzonderlijke toestand.

## Nog niet geaccepteerd

- moment en bedrag van de eerste mandate-/abonnementsbetaling;
- exacte automatische verlengings-, opzeg- en terugbetalingsvoorwaarden;
- betaalachterstand, retries en chargebackbeleid;
- definitieve checkout- en bevestigingsteksten inclusief btw- en factuurinformatie;
- live productieactivatie.

Daarom maakt deze stap nog geen Mollie-customer, payment, mandate of subscription aan en zet een ontvangen event nog geen spelerstoegang om.

## Privacy en veiligheid

- API-sleutels bestaan uitsluitend als servervariabele.
- De inbox bewaart geen naam, e-mail, omschrijving, metadata, kaart-, bank- of factuurgegevens.
- Providerreferenties verschijnen nooit in spelersresponses.
- Een tijdelijke Mollie-fout geeft een retrybare webhookresponse; een onbekende of syntactisch ongeldige ID krijgt zonder informatielek een lege `200`.
- Er vindt geen netwerkcall binnen een databasetransactie plaats.

## Terugrol

De nieuwe migratie verwijdert alleen `subscription_events`. Bestaande plannen, abonnementen, content en voortgang blijven intact. De proefactivatieschakelaar kan onafhankelijk worden uitgezet.
