import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Billing/MollieApiClient.php',
  'app/Billing/MollieMonthlyOffer.php',
  'app/Billing/MolliePaymentSnapshot.php',
  'app/Billing/ProviderWebhookInbox.php',
  'app/Billing/StartTrialWeek.php',
  'app/Http/Controllers/Billing/MollieWebhookController.php',
  'app/Models/SubscriptionEvent.php',
  'config/subscriptions.php',
  'database/migrations/2026_09_01_120000_create_subscription_events_table.php',
  'docs/decisions/ADR-005-mollie-monthly-conversion-foundation.md',
  'docs/mollie-conversion-foundation.md',
  'resources/views/player/trial-week.blade.php',
  'tests/Feature/BillingConversionFoundationTest.php',
  'tests/Feature/MollieWebhookTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [client, offer, snapshot, inbox, trial, webhook, model, config, migration, adr, docs, view, billingTests, webhookTests, apiRoutes, webRoutes, env, seeder] = await Promise.all([
  ...paths.map(read),
  read('routes/api.php'),
  read('routes/web.php'),
  read('.env.example'),
  read('database/seeders/DatabaseSeeder.php'),
]);

assert(config.includes("'provider' => 'mollie'") && config.includes("'amount_minor' => 995") && config.includes("'billing_interval' => 'month'"), 'Het goedgekeurde Mollie-aanbod van € 9,95 per maand ontbreekt');
assert(env.includes('SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED=false') && env.includes('MOLLIE_BILLING_ENABLED=false') && env.includes('MOLLIE_API_KEY='), 'De commerciële functies moeten standaard uit staan en een server-side sleutel gebruiken');
assert(migration.includes("Schema::create('subscription_events'") && migration.includes("->string('provider_event_ref')->unique()"), 'De idempotente subscription-eventinbox ontbreekt');
assert(model.includes("public $timestamps = false") && model.includes("'event_payload' => 'array'"), 'Het inboxmodel volgt het canonieke schema niet');
assert(apiRoutes.includes("Route::post('/billing/mollie/webhook'") && webRoutes.includes("Route::post('/proefweek/start'"), 'De webhook- of proefactivatieroute ontbreekt');
assert(client.includes(".'/payments/'.$paymentId") && client.includes('withToken($apiKey)') && client.includes("$response->status() === 404"), 'De callbackstatus wordt niet veilig bij Mollie geverifieerd');
assert(snapshot.includes("'amount_refunded'") && snapshot.includes("'amount_charged_back'") && !snapshot.includes('customerId') && !snapshot.includes('metadata'), 'De financiële snapshot is niet minimaal of mist een toestand');
assert(inbox.includes('firstOrCreate') && inbox.includes("'provider_event_ref' => $snapshot->eventKey()") && inbox.includes("'processing_status' => 'received'"), 'Provider-events worden niet idempotent in de inbox geplaatst');
assert(webhook.includes("preg_match('/^tr_") && webhook.includes("return response('', 503)") && webhook.includes('fetchPayment'), 'De Mollie-webhook mist invoerbegrenzing of een retrybaar foutpad');
assert(trial.includes('lockForUpdate') && trial.includes("'provider' => 'internal'") && trial.includes('addDays($plan->trial_days)'), 'De eenmalige proefactivatie is niet transactioneel of niet zeven-dagen-plan-gedreven');
assert(view.includes("$offer['price_label']") && view.includes('Start {{ $offer[\'trial_days\'] }} dagen proefweek') && view.includes('schrijft niets af'), 'De paywall toont prijs of veilige proefgrens niet');
assert(adr.includes('€ 9,95 per maand') && adr.includes('live productieactivatie') && docs.includes('geen afschrijving'), 'Besluiten en resterende menselijke poorten zijn niet volledig vastgelegd');
assert(billingTests.includes('creates_one_seven_day_internal_projection') && webhookTests.includes('deduplicated') && webhookTests.includes('personal_or_free_form_data'), 'Conversie-, idempotentie- of privacyregressietests ontbreken');
assert(!seeder.includes('madrid-maandelijks') && !seeder.includes('MOLLIE_API_KEY'), 'Deployment mag het prijsplan of providergeheim niet automatisch activeren');

console.log('Mollie-conversiefundament geldig: € 9,95 per maand, gecontroleerde proefactivatie, minimale eventinbox en live betaling standaard uit.');
