# Fase 3A — proefweek en toegangsgrenzen

## Doel

Fase 3A legt één server-side toegangsmodel onder de geplande proefweek. De speler ziet zeven missiedagen; La Espiga blijft de openbare voorbeeldmissie. Nieuwe missies worden pas startbaar wanneer zowel het accountrecht als een productiepublicatie aanwezig is.

Fase 3A activeerde **geen** proefperiode, prijs, checkout of betaalprovider. Dat waren menselijke beslispoorten volgens `AGENTS.md`. In fase 3D1 zijn Mollie en € 9,95 per maand goedgekeurd; ADR-005 beschrijft welke commerciële voorwaarden nog openstaan.

## Opgeleverde grens

- `subscription_plans` bevat een verkoopbaar plan met interval, bedrag, proefduur en rechten.
- `subscriptions` is uitsluitend de lokale toegangsprojectie. Providerreferenties zijn opaak en komen niet in spelerresponses.
- `EntitlementService` kiest de nieuwste geldige projectie en is de enige plek die rechten berekent.
- `entitled:<recht>` kan toekomstige routes server-side blokkeren.
- `/proefweek` toont zeven toegankelijke, responsieve missiedagen.
- `/spelen/proefweek/status` levert contractversie `1.0.0` volgens `docs/contracts/player-access-v1.schema.json`.

## Geldigheidsregels

| Status | Toegang |
|---|---|
| `trialing` | Vanaf `trial_starts_at` tot exclusief `trial_ends_at` |
| `active` | Binnen de huidige periode; een ontbrekend periode-einde betekent doorlopend |
| `past_due` | Tot periode-einde plus de expliciet ingestelde graceperiode; standaard 0 dagen |
| `cancelled` | Tot exclusief het effectieve periode-einde |
| `paused` / `expired` | Geen toegang |

Een proefweek gebruikt het recht `trial_week`. Tijdens `trialing` wordt maximaal de huidige kalenderdag van de proefperiode ontsloten. Een actief abonnement kan alle dagen bereiken. Ongepubliceerde missies blijven altijd `planned`, ook als het account er recht op heeft. Dag 4 is een ingebouwde persoonlijke herhalingsroute: die wordt vanaf de juiste toegangsdag beschikbaar en stelt uitsluitend reeds gespeelde, nog gepubliceerde broncontent samen.

## Privacy en veiligheid

Spelerresponses bevatten alleen status, rechten, plannaam/-code en geldigheidsmoment. Ze bevatten nooit prijsdetails, klant- of abonnementsreferenties van een provider, betaalpayloads, kaartgegevens, audio, transcript of AI-feedback.

Plan- en abonnementsrecords worden niet automatisch geseed. Daardoor kan een deployment geen onbedoelde prijs of proefvoorwaarde activeren. De latere providerintegratie moet webhooks via een idempotente inbox verwerken en mag geen netwerkcall binnen een databasetransactie uitvoeren.

Fase 3D1 houdt dit principe in stand. Het goedgekeurde plan wordt alleen door `subscriptions:install-mollie-monthly` geïnstalleerd. Proefactivatie en Mollie-statusverificatie hebben losse, standaard uitgeschakelde omgevingsschakelaars. De klassieke Mollie-callback wordt eerst buiten een transactie via de Payments API geverifieerd; alleen de gesaneerde toestand komt in `subscription_events`.

## Terugrol

De migratie verwijdert eerst `subscriptions` en daarna `subscription_plans`. Er zijn geen wijzigingen aan bestaande voortgangstabellen, waardoor La Espiga en accountvoortgang bij terugrol blijven werken.

## Vervolg binnen fase 3

1. **3B6 — station (gerealiseerd):** als gereviewde Content Studio-content met fictieve oefenreis en versiegebonden media.
2. **3C1 — persoonlijke herhaling (gerealiseerd):** maximaal vijf vervallen of nieuwe kaarten op dag 4, zonder persoonlijke antwoordopslag.
3. **3C2 — minimaal NPC-geheugen:** terugkeerherkenning uit structurele voortgang.
4. **3D1 — conversiefundament (gerealiseerd):** aanbod, proefactivatie, paywall en geverifieerde provider-eventinbox.
5. **3D2 — checkout:** mandate, terugkerende betaling, opzegging en eventprojectie na de resterende voorwaarden.
