import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Access/EntitlementService.php',
  'app/Access/EntitlementSnapshot.php',
  'app/Access/TrialWeekCatalog.php',
  'app/Http/Controllers/TrialWeekController.php',
  'app/Http/Middleware/EnsureEntitled.php',
  'app/Models/Subscription.php',
  'app/Models/SubscriptionPlan.php',
  'config/subscriptions.php',
  'database/migrations/2026_08_16_120000_create_subscription_access_tables.php',
  'docs/contracts/player-access-v1.schema.json',
  'docs/trial-week-access.md',
  'resources/views/player/trial-week.blade.php',
  'tests/Feature/TrialWeekAccessTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [service, snapshot, catalog, controller, middleware, migration, schemaSource, docs, view, routes, seeder] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[4]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read('routes/web.php'), read('database/seeders/DatabaseSeeder.php'),
]);
const schema = JSON.parse(schemaSource);
const runtime = `${service}${snapshot}${controller}${middleware}${view}`;

assert(migration.includes("Schema::create('subscription_plans'") && migration.includes("Schema::create('subscriptions'"), 'De canonieke plan- en abonnementsprojectie ontbreekt');
assert(service.includes('SubscriptionStatus::Trialing') && service.includes('SubscriptionStatus::Cancelled') && service.includes('past_due_grace_days'), 'Niet alle geldigheidsregels lopen door de EntitlementService');
assert(middleware.includes("'entitlement_required'") && middleware.includes('snapshotFor'), 'De server-side middlewaregrens ontbreekt');
assert(routes.includes("Route::get('/proefweek'") && routes.includes("Route::get('/spelen/proefweek/status'"), 'De proefweekpagina of statusroute ontbreekt');
assert(schema.properties?.data?.properties?.days?.minItems === 7 && schema.properties?.data?.properties?.days?.maxItems === 7, 'Het toegangscontract moet exact zeven missiedagen beschrijven');
assert(schema.properties?.meta?.properties?.payment_data_included?.const === false, 'Het contract moet betaaldata expliciet uitsluiten');
assert(schema.properties?.meta?.properties?.provider_references_included?.const === false, 'Het contract moet providerreferenties expliciet uitsluiten');
assert((catalog.match(/'day' =>/g) ?? []).length === 7, 'De proefweekcatalogus moet zeven dagen bevatten');
assert(view.includes('data-trial-week') && view.includes('data-trial-days') && view.includes('Nog niet te starten'), 'De toegankelijke weekweergave mist structurele toestanden');
assert(docs.includes('geen') && docs.includes('prijs') && docs.includes('menselijke beslispoorten'), 'De niet-geactiveerde commerciële grens moet expliciet gedocumenteerd zijn');
assert(!seeder.includes('SubscriptionPlan') && !seeder.includes('Subscription::'), 'Een deployment mag geen proef- of betaalvoorwaarden automatisch activeren');
assert(!runtime.includes('provider_customer_ref') && !runtime.includes('provider_subscription_ref') && !runtime.includes('amount_minor'), 'Spelerresponses mogen geen prijs- of providerreferenties dragen');

console.log('Proefweektoegang geldig: zeven dagen, centrale rechtenservice, afdwingbare middleware en geen automatische prijs of betaalactivatie.');
