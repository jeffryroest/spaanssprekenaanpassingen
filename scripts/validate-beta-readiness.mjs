import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Beta/BetaCohortMetrics.php',
  'app/Beta/BetaOperationsSnapshot.php',
  'app/Beta/SchedulerHeartbeat.php',
  'app/Console/Commands/RecordOperationsHeartbeat.php',
  'app/Http/Controllers/ContentStudio/BetaOverviewController.php',
  'app/Http/Middleware/AddSecurityHeaders.php',
  'docs/beta-readiness.md',
  'resources/views/content-studio/beta/index.blade.php',
  'tests/Feature/BetaReadinessTest.php',
];

await Promise.all(paths.map((path) => access(new URL(path, root))));

const [metrics, operations, heartbeat, heartbeatCommand, controller, headers, docs, view, tests, provider, consoleRoutes, webRoutes, bootstrap] = await Promise.all([
  ...paths.map(read),
  read('app/Providers/AppServiceProvider.php'),
  read('routes/console.php'),
  read('routes/web.php'),
  read('bootstrap/app.php'),
]);

assert(metrics.includes('whereNull(\'content_role\')') && metrics.includes('distinct()') && metrics.includes("'spoken_turns', '>', 0"), 'De cohortmeting sluit beheerders niet uit of aggregeert spelers niet veilig');
assert(metrics.includes("mission.madrid.week.final") && metrics.includes('CheckoutPaymentStatus::Paid'), 'Finale- en betaalconversie ontbreken in de bètafunnel');
assert(operations.includes('schedulerHeartbeat->isFresh()') && operations.includes("'attempt_count', '>=', 5") && operations.includes('invoiceSellerIsComplete'), 'De operationele checks missen scheduler, e-mail of factuurcontrole');
assert(heartbeat.includes('operations.scheduler.last_seen_at') && heartbeatCommand.includes("operations:heartbeat"), 'De privacyveilige scheduler-heartbeat ontbreekt');
assert(controller.includes("Gate::authorize('beta.manage')") && controller.includes("'Cache-Control' => 'private, no-store'"), 'Het bètaoverzicht is niet exclusief en private afgeschermd');
assert(provider.includes("'beta.manage'") && webRoutes.includes("Route::get('/beta'"), 'De beheerderautorisatie of bèta-route ontbreekt');
assert(consoleRoutes.includes("Schedule::command('operations:heartbeat')") && consoleRoutes.includes('everyMinute()'), 'De heartbeat wordt niet iedere minuut gepland');
assert(headers.includes("frame-ancestors 'none'") && headers.includes('microphone=(self)') && headers.includes('Strict-Transport-Security'), 'De compatibele browserbeveiligingsbasis ontbreekt');
assert(bootstrap.includes('append(AddSecurityHeaders::class)'), 'De beveiligingsheaders zijn niet globaal geactiveerd');
assert(view.includes('Geaggregeerde voortgang') && view.includes('geen nieuwe persoonsgegevens') && !view.includes('->email') && !view.includes('$order'), 'De beheerpagina bewaakt de afgesproken privacygrens niet');
assert(docs.includes('geen trackingcookies') && docs.includes('volledige nonce-gebaseerde') && docs.includes('bewaartermijn'), 'Privacygrenzen en resterende productiepoorten zijn niet gedocumenteerd');
assert(tests.includes('exclusive_to_administrators') && tests.includes('without_personal_data') && tests.includes('security_headers'), 'Autorisatie-, privacy- of beveiligingsregressietests ontbreken');

console.log('Fase 4A geldig: privacybewuste cohortmeting, operationele bèta-checks, scheduler-heartbeat en veilige browserheaders.');
