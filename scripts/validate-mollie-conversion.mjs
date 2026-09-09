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
  'app/Billing/ProcessMolliePayment.php',
  'app/Billing/ProviderWebhookInbox.php',
  'app/Billing/StartMollieCheckout.php',
  'app/Billing/StartTrialWeek.php',
  'app/Http/Controllers/Billing/MollieWebhookController.php',
  'app/Http/Controllers/ContentStudio/BillingOverviewController.php',
  'app/Models/SubscriptionEvent.php',
  'config/subscriptions.php',
  'database/migrations/2026_09_01_120000_create_subscription_events_table.php',
  'database/migrations/2026_09_01_130000_create_subscription_orders_table.php',
  'docs/decisions/ADR-005-mollie-monthly-conversion-foundation.md',
  'docs/mollie-conversion-foundation.md',
  'docs/mollie-billing-operations.md',
  'resources/views/content-studio/billing/index.blade.php',
  'resources/views/player/trial-week.blade.php',
  'tests/Feature/BillingConversionFoundationTest.php',
  'tests/Feature/MollieWebhookTest.php',
  'tests/Feature/MollieCheckoutSubscriptionTest.php',
  'tests/Feature/BillingOperationsTest.php',
  'app/Billing/BillingEmailPlanner.php',
  'app/Billing/CheckoutBuyer.php',
  'app/Billing/InvoicePdf.php',
  'app/Billing/IssueBillingInvoice.php',
  'app/Console/Commands/SendDueBillingEmails.php',
  'app/Http/Controllers/Billing/BillingInvoiceController.php',
  'app/Models/BillingInvoice.php',
  'database/migrations/2026_09_09_090000_add_billing_recovery_and_invoicing.php',
  'docs/mollie-recovery-invoicing.md',
  'resources/views/mail/billing/payment-recovery.blade.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [client, offer, snapshot, processor, inbox, checkout, trial, webhook, operationsController, model, config, eventMigration, orderMigration, adr, docs, operationsDocs, operationsView, view, billingTests, webhookTests, checkoutTests, operationsTests, emailPlanner, checkoutBuyer, invoicePdf, invoiceIssuer, emailCommand, invoiceController, invoiceModel, recoveryMigration, recoveryDocs, recoveryMail, apiRoutes, webRoutes, env, seeder] = await Promise.all([
  ...paths.map(read),
  read('routes/api.php'),
  read('routes/web.php'),
  read('.env.example'),
  read('database/seeders/DatabaseSeeder.php'),
]);

assert(config.includes("'provider' => 'mollie'") && config.includes("'amount_minor' => 995") && config.includes("'billing_interval' => 'month'"), 'Het goedgekeurde Mollie-aanbod van € 9,95 per maand ontbreekt');
assert(config.includes("'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 14)") && config.includes("'treatment' => 'exempt'") && config.includes("'legal_name' => env('BILLING_SELLER_LEGAL_NAME', '')"), 'De goedgekeurde hersteltermijn of veilige btw-vrijgestelde factuurafzenderconfiguratie ontbreekt');
assert(env.includes('SUBSCRIPTION_TRIAL_ACTIVATION_ENABLED=false') && env.includes('MOLLIE_BILLING_ENABLED=false') && env.includes('MOLLIE_CHECKOUT_ENABLED=false') && env.includes('MOLLIE_API_KEY='), 'De commerciële functies moeten standaard uit staan en een server-side sleutel gebruiken');
assert(eventMigration.includes("Schema::create('subscription_events'") && eventMigration.includes("->string('provider_event_ref')->unique()"), 'De idempotente subscription-eventinbox ontbreekt');
assert(orderMigration.includes("Schema::create('subscription_orders'") && orderMigration.includes("->string('first_name', 100)") && orderMigration.includes("->string('last_name', 160)") && orderMigration.includes("->string('email', 254)") && orderMigration.includes("->string('payment_status', 32)"), 'De ordersnapshot met besteller en betaalstatus ontbreekt');
assert(model.includes("public $timestamps = false") && model.includes("'event_payload' => 'array'"), 'Het inboxmodel volgt het canonieke schema niet');
assert(apiRoutes.includes("Route::post('/billing/mollie/webhook'") && webRoutes.includes("Route::post('/proefweek/start'") && webRoutes.includes("Route::get('/betalingen'") && webRoutes.includes("Route::post('/betalingen/zoeken'"), 'De webhook-, proefactivatie- of betaalbeheerroute ontbreekt');
assert(client.includes("$this->url('/payments/'.$paymentId)") && client.includes('withToken($apiKey)') && client.includes("$response->status() === 404") && client.includes("'sequenceType' => 'first'") && client.includes("'interval' => '1 month'"), 'De Mollie-status- of recurring-flow is niet veilig geïmplementeerd');
const safePayload = snapshot.slice(snapshot.indexOf('public function safePayload'));
assert(safePayload.includes("'amount_refunded'") && safePayload.includes("'amount_charged_back'") && !safePayload.includes('customerId') && !safePayload.includes('checkoutReference') && !safePayload.includes('metadata'), 'De opgeslagen financiële snapshot is niet minimaal of mist een toestand');
assert(inbox.includes('firstOrCreate') && inbox.includes("'provider_event_ref' => $snapshot->eventKey()") && inbox.includes("'processing_status' => 'received'"), 'Provider-events worden niet idempotent in de inbox geplaatst');
assert(processor.includes('hasUsableMandate') && processor.includes('addMonthNoOverflow') && processor.includes("'processing_status' => 'processed'"), 'Betaalde events worden niet veilig naar maandelijkse toegang geprojecteerd');
assert(checkout.includes('...$buyer->orderAttributes()') && checkout.includes("'consent_version'") && checkoutBuyer.includes("'purchase_type' => $this->purchaseType") && checkoutBuyer.includes("'billing_country' => $this->billingCountry"), 'De checkout registreert de vereiste particuliere of zakelijke besteller en toestemming niet');
assert(webhook.includes("preg_match('/^tr_") && webhook.includes("return response('', 503)") && webhook.includes('fetchPayment'), 'De Mollie-webhook mist invoerbegrenzing of een retrybaar foutpad');
assert(trial.includes('lockForUpdate') && trial.includes("'provider' => 'internal'") && trial.includes('addDays($plan->trial_days)'), 'De eenmalige proefactivatie is niet transactioneel of niet zeven-dagen-plan-gedreven');
assert(view.includes("$offer['price_label']") && view.includes('Start {{ $offer[\'trial_days\'] }} dagen proefweek') && view.includes('schrijft niets af') && view.includes('name="first_name"') && view.includes('name="last_name"') && view.includes('name="email"') && view.includes('name="recurring_consent"'), 'De paywall toont prijs, proefgrens of bestellerformulier niet');
assert(adr.includes('€ 9,95 per maand') && adr.includes('live productieactivatie') && docs.includes('sequenceType=first'), 'Besluiten en resterende menselijke poorten zijn niet volledig vastgelegd');
assert(operationsController.includes("Gate::authorize('billing.manage')") && operationsController.includes('CheckoutPaymentStatus::cases()') && operationsController.includes("'Cache-Control' => 'private, no-store'"), 'Het betaaloverzicht mist beheerderautorisatie, statusfilters of private caching');
assert(model.includes('attentionKind') && model.includes('Betaling teruggeboekt'), 'Financiële uitzonderingen worden niet herkenbaar geclassificeerd');
assert(snapshot.includes('hasFinancialReversal') && processor.includes("'mollie_'.$status->value"), 'Terugdraaiingen missen een veilig incidentmoment of stabiele ordercode');
assert(operationsView.includes('method="POST"') && operationsView.includes('Alleen beheerders') && operationsView.includes('geen kaart- of bankgegevens') && operationsDocs.includes('verandert de toegang niet automatisch'), 'De beheer- en privacygrens van 3D3A is niet zichtbaar vastgelegd');
assert(recoveryMigration.includes("->timestamp('grace_ends_at', 6)") && recoveryMigration.includes("Schema::create('billing_invoices'") && recoveryMigration.includes("Schema::create('billing_email_deliveries'"), 'De herstel-, factuur- of e-mailprojectie ontbreekt');
assert(processor.includes('SubscriptionStatus::PastDue') && processor.includes('pauseForReversal') && processor.includes('$this->invoices->handle'), 'Het goedgekeurde achterstands-, terugboekings- of factuurbeleid wordt niet geprojecteerd');
assert(invoiceIssuer.includes("sprintf('%s-%d-%06d'") && invoiceIssuer.includes("'tax_treatment' => 'exempt'") && invoiceModel.includes("'seller_snapshot' => 'array'"), 'De opeenvolgende btw-vrijgestelde factuursnapshot ontbreekt');
assert(invoicePdf.includes('%PDF-1.4') && invoiceController.includes("'Content-Type' => 'application/pdf'") && invoiceController.includes("'Cache-Control' => 'private, no-store'"), 'De afgeschermde pdf-factuurdownload ontbreekt');
assert(emailPlanner.includes("'payment_recovery_day_'.$day") && emailPlanner.includes('cancelRecoveryMessages') && emailCommand.includes("billing:send-due-emails") && recoveryMail.includes('schrijft nooit direct geld af'), 'De veilige dag-0/7/13-herstelcommunicatie ontbreekt');
assert(recoveryDocs.includes('veertien dagen') && recoveryDocs.includes('afgeschermde servervariabelen') && recoveryDocs.includes('bewaartermijn'), 'Besluiten, veilige factuurafzenderconfiguratie of resterende retentiepoort zijn niet gedocumenteerd');
assert(billingTests.includes('creates_one_seven_day_internal_projection') && webhookTests.includes('deduplicated') && webhookTests.includes('personal_or_free_form_data'), 'Conversie-, idempotentie- of privacyregressietests ontbreken');
assert(checkoutTests.includes('registers_buyer_and_recurring_consent') && checkoutTests.includes('creates_monthly_subscription') && checkoutTests.includes('cancellation_keeps_access_until_period_end'), 'Checkout-, projectie- of opzegregressietests ontbreken');
assert(operationsTests.includes('exclusive_to_administrators') && operationsTests.includes('blocks_access') && operationsTests.includes('fourteen_day_grace_period') && operationsTests.includes('cancel_on_behalf'), 'Autorisatie-, incident-, herstel- of beheeropzegtests ontbreken');
assert(!seeder.includes('madrid-maandelijks') && !seeder.includes('MOLLIE_API_KEY'), 'Deployment mag het prijsplan of providergeheim niet automatisch activeren');

console.log('Mollie-checkout geldig: € 9,95 per maand, conditionele factuurgegevens, veertien dagen betaalherstel, btw-vrijgestelde pdf-facturen en live checkout standaard uit.');
