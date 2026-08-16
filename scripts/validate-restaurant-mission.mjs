import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteRestaurantMission.php',
  'app/Access/TrialWeekCatalog.php',
  'app/ContentStudio/PlayableContentTemplates.php',
  'app/ContentStudio/RuntimeReadiness.php',
  'app/Http/Controllers/Game/CompleteRestaurantMissionController.php',
  'app/Http/Requests/Game/AssessTurnRequest.php',
  'app/Rules/PlayableDomainData.php',
  'content/examples/restaurant-dialogue-domain-data.json',
  'docs/contracts/restaurant-dialogue-v1.schema.json',
  'docs/restaurant-mission.md',
  'resources/js/game/madrid-hub.js',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/madrid.blade.php',
  'resources/views/game/restaurant.blade.php',
  'resources/views/player/progress.blade.php',
  'tests/Feature/RestaurantMissionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, catalog, templates, readiness, request, rule, exampleSource, schemaSource, docs, hub, dialogue, hubView, view, progress, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[5]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read(paths[12]), read(paths[13]), read(paths[14]), read('routes/web.php'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'restaurant_text_dialogue' && example.npc.id === 'npc.carmen.santos', 'De restaurantidentiteit of Carmen ontbreekt');
assert(example.mission.id === 'mission.madrid.restaurant.order' && example.mission.required_text_turns === 5, 'De restaurantmissie moet exact vijf actieve beurten hebben');
assert(example.steps.length === 7 && stepIds.size === 7, 'De restaurantmissie moet zeven unieke contentstappen hebben');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau mist een restaurantcomplicatie');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere restaurantbeurt moet optionele voorbeeldsteun bieden');
assert(schema.properties?.scene?.const === 'restaurant_text_dialogue' && schema.properties?.mission?.properties?.required_text_turns?.const === 5, 'Het restaurantcontract begrenst de route niet');
assert(example.runtime_access?.visibility === 'entitled' && schema.properties?.runtime_access?.properties?.entitlement?.const === 'trial_week', 'Restaurantcontent moet afgeschermde proefweekcontent zijn');
assert(routes.includes("Route::view('/spelen/madrid/restaurant'") && routes.includes("Route::post('/spelen/madrid/restaurant/voltooien'") && routes.includes('entitled:trial_week'), 'De restaurant-routes missen hun proefweekgrens');
assert(catalog.includes("'conversation_slug' => 'restaurant-el-reloj'") && catalog.includes("'route' => 'game.madrid.restaurant'"), 'Dag 3 controleert de restaurantpublicatie niet');
assert(request.includes("'restaurant-el-reloj'") && dialogue.includes('scenario_slug: scenarioSlug'), 'Restaurantfeedback is niet aan de eigen scenarioslug gekoppeld');
assert(view.includes('data-restaurant-dialogue') && view.includes('data-scenario-slug="restaurant-el-reloj"'), 'De herbruikbare dialoogclient is niet op het restaurant aangesloten');
assert(view.includes("route('game.madrid.restaurant.content'") && view.includes('WebM/Opus'), 'De privécontent- of spreekroute ontbreekt in de restaurantscène');
assert(action.includes("'stamp.first_madrid_dinner'") && action.includes("'madrid.health.preview'"), 'Restaurantbeloningen of de dag-5-vooruitblik ontbreken');
assert(templates.includes("'restaurant-el-reloj'") && readiness.includes("'restaurant_text_dialogue'"), 'Content Studio mist de restaurantstarter of runtimecontrole');
assert(rule.includes("'restaurant_text_dialogue'") && docs.includes('nooit automatisch'), 'De redactionele contract- of publicatiegrens ontbreekt');
assert(hubView.includes('data-restaurant-route') && hub.includes('restaurantDay?.action_url'), 'Café El Reloj reageert niet op de gepubliceerde dag-3-missie');
assert(progress.includes('restaurantProgress'), 'Het accountdashboard toont de restaurantvoortgang niet');

console.log('Restaurantmissie geldig: vijf actieve beurten, drie niveaupaden, Carmen, privéproductiecontent, visuele restaurantscène en duurzame dag-3-beloningen.');
