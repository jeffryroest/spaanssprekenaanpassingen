import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteScenarioMission.php',
  'app/Actions/PlayerProgress/CompleteTaxiMission.php',
  'app/Access/TrialWeekCatalog.php',
  'app/Feedback/PublishedConversationTurnResolver.php',
  'app/Http/Controllers/Game/CompleteTaxiMissionController.php',
  'app/Http/Controllers/Game/EntitledConversationController.php',
  'app/PlayerProgress/PublishedScenarioMission.php',
  'app/PlayerProgress/ScenarioMissionDefinition.php',
  'content/examples/taxi-dialogue-domain-data.json',
  'docs/contracts/taxi-dialogue-v1.schema.json',
  'docs/taxi-mission.md',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/taxi.blade.php',
  'tests/Feature/TaxiMissionTest.php',
  'app/ContentApi/RuntimeContentAccess.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, taxi, catalog, resolver, contentController, published, definition, exampleSource, schemaSource, docs, dialogue, view, runtimeAccess, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[5]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read(paths[12]), read(paths[14]), read('routes/web.php'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'taxi_text_dialogue' && example.npc.id === 'npc.diego.ruiz', 'De taxi-identiteit of Diego ontbreekt');
assert(example.mission.id === 'mission.madrid.taxi.ride' && example.mission.required_text_turns === 5, 'De taxi-missie moet exact vijf beurten hebben');
assert(example.steps.length === 7 && stepIds.size === 7, 'De taxiroute moet zeven unieke contentstappen hebben');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau mist een taxicomplicatie');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere beurt moet optionele voorbeeldsteun bieden');
assert(schema.properties?.scene?.const === 'taxi_text_dialogue' && schema.properties?.mission?.properties?.required_text_turns?.const === 5, 'Het taxi-contract begrenst de route niet');
assert(example.runtime_access?.visibility === 'entitled' && schema.properties?.runtime_access?.properties?.entitlement?.const === 'trial_week', 'De taxi-content moet als proefweekcontent zijn gemarkeerd');
assert(routes.includes("Route::view('/spelen/madrid/taxi'") && routes.includes('entitled:trial_week'), 'De speelroute mist de proefweekgrens');
assert(routes.includes("Route::post('/spelen/madrid/taxi/voltooien'") && routes.includes('throttle:mission-completions'), 'De voltooiingsroute mist rate limiting');
assert(catalog.includes("'conversation_slug' => 'taxi-diego'") && catalog.includes('latestProductionItem'), 'Dag 2 moet de productiepublicatie controleren');
assert(resolver.includes('string $scenarioSlug') && dialogue.includes('scenario_slug: scenarioSlug'), 'Feedbackcontext is nog hard aan La Espiga gekoppeld');
assert(dialogue.includes("querySelector('[data-scenario-dialogue]')") && view.includes('data-scenario-slug="taxi-diego"'), 'De herbruikbare dialoogclient is niet aangesloten');
assert(view.includes("route('game.madrid.taxi.content'") && contentController.includes("'Cache-Control' => 'private, no-store'"), 'De taxi mag niet via de publieke contentfeed worden geladen');
assert(runtimeAccess.includes('allowsEntitlement') && catalog.includes('RuntimeContentAccess') && published.includes('RuntimeContentAccess'), 'De publicatie-, start- en opslaggrenzen moeten hetzelfde toegangscontract afdwingen');
assert(published.includes('latestProductionItem') && definition.includes('validateCompletion'), 'Accountopslag moet de gepubliceerde taxiroute opnieuw valideren');
assert(action.includes('DB::transaction') && action.includes('lockForUpdate') && action.includes('completion_key_conflict'), 'De generieke missietransactie mist locking of idempotency');
assert(taxi.includes("'stamp.first_taxi_ride'") && taxi.includes("'madrid.restaurant.preview'"), 'Taxibeloningen of dag-3-vooruitblik ontbreken');
assert(docs.includes('publiceert') && docs.includes('nooit automatisch'), 'De Content Studio-publicatiegrens moet expliciet zijn');
assert(!action.includes('answer') && !action.includes('transcript') && !action.includes('audio') && !action.includes('feedback'), 'Taxi-accountvoortgang mag geen gevoelige gespreksinhoud opslaan');

console.log('Taximissie geldig: vijf actieve beurten, drie niveaupaden, herbruikbare spreekmotor, productiecontent en server-side toegangs- en voortgangsgrenzen.');
