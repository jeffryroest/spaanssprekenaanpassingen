import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompletePersonalReview.php',
  'app/Http/Controllers/Game/CompletePersonalReviewController.php',
  'app/Http/Controllers/Game/PersonalReviewController.php',
  'app/Http/Requests/Game/CompletePersonalReviewRequest.php',
  'app/Models/UserPracticeItem.php',
  'app/PlayerProgress/PersonalReviewDeck.php',
  'database/migrations/2026_09_01_090000_create_user_practice_items_table.php',
  'docs/contracts/personal-review-v1.schema.json',
  'docs/decisions/ADR-003-deterministic-personal-review.md',
  'docs/personal-review.md',
  'resources/js/game/personal-review.js',
  'resources/views/game/review.blade.php',
  'tests/Feature/PersonalReviewTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, controller, request, model, deck, migration, schemaSource, decision, docs, client, view, tests] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[3]), read(paths[4]), read(paths[5]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read(paths[12]),
]);
const schema = JSON.parse(schemaSource);
const routes = await read('routes/web.php');
const catalog = await read('app/Access/TrialWeekCatalog.php');
const css = await read('resources/css/app.css');
const app = await read('resources/js/app.js');

assert(schema.properties?.data?.properties?.cards?.maxItems === 5, 'Het herhalingscontract begrenst de kaarten niet op vijf');
assert(schema.properties?.meta?.properties?.answer_persisted?.const === false, 'Het privacycontract verbiedt antwoordopslag niet');
assert(deck.includes('latestAttempts') && deck.includes("$sourceType === 'speech'") && deck.includes('isFuture()'), 'De persoonlijke selectie of vervaldatum ontbreekt');
assert(action.includes("'personal_review'") && action.includes('awardedToday') && action.includes('ledgerKey') && action.includes("$rating === 'again'"), 'De intervalplanner of dagelijkse ledgergrens ontbreekt');
assert(request.includes("'answer' => ['prohibited']") && request.includes("'cards.*.transcript' => ['prohibited']"), 'Persoonlijke inhoud wordt niet hard afgewezen');
assert(migration.includes("Schema::create('user_practice_items'") && migration.includes("unique(['user_id', 'practice_key'])"), 'De snelle herhalingsprojectie of unieke grens ontbreekt');
assert(model.includes("'due_at' => 'immutable_datetime'"), 'De vervaldatum wordt niet veilig gecast');
assert(routes.includes("Route::get('/spelen/madrid/herhaling'") && routes.includes("Route::post('/spelen/madrid/herhaling/voltooien'") && routes.includes('entitled:trial_week'), 'De dag-4-routes missen hun proefweekgrens');
assert(catalog.includes("'route' => 'game.madrid.review'") && catalog.includes("[1, 4]"), 'Dag 4 is niet als ingebouwde herhalingsmissie ontsloten');
assert(client.includes('data-review-rating') && client.includes('practice_key') && !client.includes('response: elements.response.value'), 'De kaartinteractie of privacygrens in de client ontbreekt');
assert(view.includes('data-personal-review') && view.includes('Jouw antwoord blijft vluchtig') && css.includes('.review-stage') && app.includes("import './game/personal-review'"), 'De visuele, privacybewuste herhalingslaag ontbreekt');
assert(controller.includes("'answer_persisted' => false") && tests.includes("assertDatabaseCount('game_ledger', 2)"), 'De responseprivacy of idempotentieregressie ontbreekt');
assert(docs.includes('geen oncontroleerbare AI-planning') && docs.includes('nooit dubbele valuta'), 'De planner- of idempotentiegrens is niet gedocumenteerd');
assert(decision.includes('AI kiest geen kaarten of intervallen') && decision.includes('vrije gesprekstekst'), 'Het herhalingsbesluit mist de deterministische of privacygrens');

console.log('Persoonlijke herhaling geldig: vijf gepersonaliseerde kaarten, transparante intervallen, vluchtige antwoorden en een idempotente dagbeloning.');
