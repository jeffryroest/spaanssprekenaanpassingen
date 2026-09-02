# Fase 3D1–3D2 — Mollie-conversie en checkoutkern

## Opgeleverd

- Het vastgestelde aanbod is `Spaansspreken Madrid`, € 9,95 per maand via Mollie.
- `subscriptions:install-mollie-monthly` installeert dit plan idempotent en weigert een bestaand afwijkend plan te overschrijven.
- Een ingelogde speler kan, na gecontroleerde activatie, precies één zeven-daagse proefperiode starten.
- De proefweekpagina toont het aanbod en een paywalltoestand; de openbare eerste missie blijft beschikbaar zonder recht.
- `POST /api/billing/mollie/webhook` verifieert een klassieke Mollie-callback door de actuele betaling via de Payments API op te halen.
- De `subscription_events`-inbox dedupliceert dezelfde geverifieerde financiële toestand en bewaart alleen een minimale status-snapshot.
- De checkout registreert voornaam, achternaam, e-mailadres, toestemmingsversie, bedrag en status in `subscription_orders`.
- De eerste betaling van € 9,95 gebruikt `sequenceType=first`; na bevestiging wordt een eindeloos Mollie-abonnement met interval `1 month` vanaf één kalendermaand later aangemaakt.
- Alleen server-side geverifieerde betaalstatussen wijzigen de lokale abonnementsprojectie.
- Opzeggen gebeurt bij Mollie en houdt de lokale toegang in stand tot het huidige periode-einde.

## Bewuste veiligheidsgrenzen

`SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED`, `MOLLIE_BILLING_ENABLED` en `MOLLIE_CHECKOUT_ENABLED` staan standaard uit. De Mollie API-sleutel wordt nooit in code, databasepayloads of spelerresponses opgenomen. Zet `MOLLIE_CHECKOUT_ENABLED` pas aan na gecontroleerde test- en productieconfiguratie.

De inbox bewaart alleen:

- payment-ID en eventuele subscription-ID;
- status, valuta en bedragen;
- statusmomenten voor betaald, mislukt, geannuleerd of verlopen.

Vrije omschrijvingen, metadata, customer-ID, naam, e-mail, betaalinstrument en volledige providerpayload worden niet in de eventinbox opgeslagen. De customer-ID en besteller worden wel doelgebonden in de afzonderlijke ordersnapshot bewaard; kaart- en bankgegevens nooit.

## Installatie en gecontroleerde activatie

```bash
php artisan migrate --force
php artisan subscriptions:install-mollie-monthly --dry-run
php artisan subscriptions:install-mollie-monthly
```

Zet daarna alleen voor proefactivatie:

```dotenv
SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED=true
```

Mollie-eventverificatie vereist daarnaast server-side:

```dotenv
MOLLIE_BILLING_ENABLED=true
MOLLIE_API_KEY=test_xxx
```

Activeer checkout afzonderlijk, eerst met een Mollie-testsleutel:

```dotenv
MOLLIE_CHECKOUT_ENABLED=true
```

De webhook-URL is `https://<host>/api/billing/mollie/webhook`. De returnroute bevat alleen de publieke lokale order-ULID; de server vertrouwt de redirect niet als betaalbewijs.

## Webhookgedrag

Mollie stuurt bij de klassieke webhook een form-veld `id`. De server accepteert uitsluitend het betalings-ID-formaat, haalt `GET /v2/payments/{id}` op en antwoordt:

| Situatie | Antwoord | Inbox |
|---|---:|---|
| ongeldige of onbekende ID | 200 | niets |
| geverifieerde bekende toestand | 200 | één `processed` event en zo nodig projectie-update |
| geverifieerde onbekende toestand | 200 | één veilig `ignored` event |
| identieke herhaling | 200 | bestaand verwerkt event |
| Mollie tijdelijk onbereikbaar/ongeldig antwoord | 503 | niets; Mollie kan opnieuw proberen |

Bronnen: [Mollie webhooks](https://docs.mollie.com/reference/webhooks), [Mollie recurring payments](https://docs.mollie.com/docs/recurring-payments), [Mollie customers](https://docs.mollie.com/reference/create-customer), [eerste customer-payment](https://docs.mollie.com/reference/create-customer-payment), [subscriptions](https://docs.mollie.com/reference/create-subscription), [opzeggen](https://docs.mollie.com/reference/cancel-subscription) en [API-idempotentie](https://docs.mollie.com/reference/api-idempotency).

## Vervolg

De checkoutkern is gerealiseerd. Vervolgwerk richt zich op het expliciete beleid en de gebruikersroutes voor terugbetalingen, chargebacks, betaalachterstand/retries, facturen/btw en definitieve bewaartermijnen. Productieactivatie blijft een afzonderlijke menselijke poort.
