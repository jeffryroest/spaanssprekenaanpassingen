# ADR-005 · Mollie-maandaanbod en veilige conversiegrens

Status: gedeeltelijk geaccepteerd; checkoutkern geaccepteerd

## Geaccepteerde besluiten

- Betaalprovider: Mollie.
- Consumentenaanbod: € 9,95 per maand (`EUR 995` in minor units).
- De bestaande proefweek duurt zeven dagen.
- De proefweek start zonder betaalgegevens.
- Na de proefweek kiest de speler expliciet voor checkout: de eerste maand wordt direct voor € 9,95 betaald en daarna maandelijks verlengd.
- Opzeggen stopt de automatische verlenging en laat toegang doorlopen tot het einde van de betaalde periode.
- Bij de bestelling worden minimaal voornaam, achternaam, e-mailadres en de status van de eerste betaling geregistreerd.
- Na verlopen toegang blijft alleen de openbare voorbeeldmissie beschikbaar.

## Technisch besluit

Het commerciële plan staat versiegebonden in configuratie en wordt uitsluitend via een bewust, idempotent beheercommando in de database geplaatst. Proefactivatie, Mollie-verkeer en checkout hebben afzonderlijke, standaard uitgeschakelde omgevingsschakelaars.

De klassieke Mollie-webhook wordt niet vertrouwd als financiële statusbron. De callback bevat alleen een betalings-ID. De backend haalt de actuele betaling met de server-side API-sleutel bij Mollie op en bewaart alleen een gesaneerde status-snapshot in de bestaande `subscription_events`-inbox. Herhaalde callbacks met dezelfde financiële toestand leveren één event op; een latere refund of chargeback blijft een afzonderlijke toestand.

Checkout maakt eerst een lokale `subscription_order` met de vastgelegde besteller en toestemmingsversie. Daarna maakt de backend idempotent een Mollie-customer en eerste customer-payment met `sequenceType=first`. De redirect activeert niets: zowel de returnroute als webhook halen de actuele betaling opnieuw bij Mollie op. Alleen een passend betaald bedrag van EUR 9,95, dezelfde customer en dezelfde orderreferentie kan toegang activeren.

Na de betaalbevestiging controleert de backend het mandaat en maakt of vindt hij idempotent het maandabonnement. De lokale `subscription` is uitsluitend de toegangsprojectie. Een geslaagde terugkerende betaling schuift de periode een kalendermaand door. Een mislukte terugkerende betaling verlengt de lokale periode niet. Opzeggen wordt eerst bij Mollie uitgevoerd en zet daarna lokaal `cancel_at_period_end`; er staat geen provider-call binnen een databasetransactie.

## Nog niet geaccepteerd

- betaalachterstand, retries en chargebackbeleid;
- terugbetalingsbeleid en het effect daarvan op reeds verleende toegang;
- definitieve factuur- en btw-informatie;
- definitieve juridische bewaartermijnen voor bestelgegevens;
- live productieactivatie.

## Privacy en veiligheid

- API-sleutels bestaan uitsluitend als servervariabele.
- De ordersnapshot bewaart voornaam, achternaam, e-mailadres, aanbod, bedrag, toestemming en betaalstatus. Dit is doelbewuste besteladministratie en staat niet in logs of spelstatus.
- De inbox bewaart geen naam, e-mail, vrije omschrijving, metadata, kaart-, bank- of factuurgegevens. Customer- en orderreferenties worden alleen tijdens verificatie gebruikt en niet in de gesaneerde eventpayload opgenomen.
- Providerreferenties verschijnen nooit in spelersresponses.
- Een tijdelijke Mollie-fout geeft een retrybare webhookresponse; een onbekende of syntactisch ongeldige ID krijgt zonder informatielek een lege `200`.
- Er vindt geen netwerkcall binnen een databasetransactie plaats.

## Terugrol

De 3D2-migratie verwijdert bij terugrol alleen `subscription_orders`; providerobjecten worden niet automatisch verwijderd. Bestaande plannen, abonnementen, events, content en voortgang blijven intact. De checkoutschakelaar kan onafhankelijk worden uitgezet om nieuwe orders direct te stoppen.
