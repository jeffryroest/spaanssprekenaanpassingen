# Fase 3D1 — Mollie-conversiefundament

## Opgeleverd

- Het vastgestelde aanbod is `Spaansspreken Madrid`, € 9,95 per maand via Mollie.
- `subscriptions:install-mollie-monthly` installeert dit plan idempotent en weigert een bestaand afwijkend plan te overschrijven.
- Een ingelogde speler kan, na gecontroleerde activatie, precies één zeven-daagse proefperiode starten.
- De proefweekpagina toont het aanbod en een paywalltoestand; de openbare eerste missie blijft beschikbaar zonder recht.
- `POST /api/billing/mollie/webhook` verifieert een klassieke Mollie-callback door de actuele betaling via de Payments API op te halen.
- De `subscription_events`-inbox dedupliceert dezelfde geverifieerde financiële toestand en bewaart alleen een minimale status-snapshot.

## Bewuste veiligheidsgrenzen

Deze fase voert geen afschrijving uit en verwerkt inboxevents nog niet naar toegang. `SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED` en `MOLLIE_BILLING_ENABLED` staan standaard uit. De Mollie API-sleutel wordt nooit in code, databasepayloads of spelerresponses opgenomen.

De inbox bewaart alleen:

- payment-ID en eventuele subscription-ID;
- status, valuta en bedragen;
- statusmomenten voor betaald, mislukt, geannuleerd of verlopen.

Vrije omschrijvingen, metadata, customer-ID, naam, e-mail, betaalinstrument en volledige providerpayload worden niet opgeslagen.

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

Gebruik tijdens ontwikkeling eerst een Mollie-testsleutel. De webhook-URL is `https://<host>/api/billing/mollie/webhook`. Live checkout blijft geblokkeerd totdat de open voorwaarden uit ADR-005 zijn bevestigd.

## Webhookgedrag

Mollie stuurt bij de klassieke webhook een form-veld `id`. De server accepteert uitsluitend het betalings-ID-formaat, haalt `GET /v2/payments/{id}` op en antwoordt:

| Situatie | Antwoord | Inbox |
|---|---:|---|
| ongeldige of onbekende ID | 200 | niets |
| geverifieerde nieuwe toestand | 200 | één `received` event |
| identieke herhaling | 200 | bestaand event |
| Mollie tijdelijk onbereikbaar/ongeldig antwoord | 503 | niets; Mollie kan opnieuw proberen |

Bronnen: [Mollie webhooks](https://docs.mollie.com/reference/webhooks), [Mollie recurring payments](https://docs.mollie.com/docs/recurring-payments), [Mollie authentication](https://docs.mollie.com/reference/authentication).

## Vervolg

3D2 voegt pas na de resterende productbesluiten de customer/mandate/checkoutflow, eventverwerking naar de lokale abonnementsprojectie, opzegroute en herstelpaden toe.
