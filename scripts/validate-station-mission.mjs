import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteStationMission.php',
  'app/Access/TrialWeekCatalog.php',
  'app/ContentStudio/PlayableContentTemplates.php',
  'app/ContentStudio/RuntimeReadiness.php',
  'app/Http/Controllers/Game/CompleteStationMissionController.php',
  'app/Http/Requests/Game/AssessTurnRequest.php',
  'app/PlayerProgress/PlayerProgressSnapshot.php',
  'app/Rules/PlayableDomainData.php',
  'content/examples/station-dialogue-domain-data.json',
  'docs/contracts/station-dialogue-v1.schema.json',
  'docs/station-mission.md',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/station.blade.php',
  'resources/views/player/progress.blade.php',
  'tests/Feature/StationMissionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [
  action, catalog, templates, readiness, request, progressSnapshot, rule, exampleSource,
  schemaSource, docs, dialogue, view, progress, routes,
] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[5]), read(paths[6]),
  read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read(paths[12]),
  read(paths[13]), read('routes/web.php'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const inspector = await read('app/ContentStudio/PlayableContentInspector.php');
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'station_text_dialogue' && example.npc.id === 'npc.nuria.martin', 'De stationsidentiteit of werk-NPC Nuria ontbreekt');
assert(example.mission.id === 'mission.madrid.station.ticket' && example.mission.required_text_turns === 5, 'De stationmissie moet exact vijf actieve beurten hebben');
assert(example.steps.length === 7 && stepIds.size === 7, 'De stationmissie moet zeven unieke contentstappen hebben');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau mist een stationscomplicatie');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere stationsbeurt moet optionele voorbeeldsteun bieden');
assert(schema.properties?.scene?.const === 'station_text_dialogue' && schema.properties?.mission?.properties?.required_text_turns?.const === 5, 'Het stationscontract begrenst de route niet');
assert(example.runtime_access?.visibility === 'entitled' && schema.properties?.runtime_access?.properties?.entitlement?.const === 'trial_week', 'Stationscontent moet afgeschermde proefweekcontent zijn');
assert(routes.includes("Route::view('/spelen/madrid/station'") && routes.includes("Route::post('/spelen/madrid/station/voltooien'") && routes.includes('entitled:trial_week'), 'De stationsroutes missen hun proefweekgrens');
assert(catalog.includes("'conversation_slug' => 'station-nuria'") && catalog.includes("'route' => 'game.madrid.station'"), 'Dag 6 controleert de stationspublicatie niet');
assert(request.includes("'station-nuria'") && dialogue.includes('scenario_slug: scenarioSlug'), 'Stationsfeedback is niet aan de eigen scenarioslug gekoppeld');
assert(view.includes('data-station-dialogue') && view.includes('data-scenario-slug="station-nuria"'), 'De herbruikbare dialoogclient is niet op het station aangesloten');
assert(view.includes("route('game.madrid.station.content'") && view.includes('WebM/Opus'), 'De privécontent- of spreekroute ontbreekt in de stationsscène');
assert(action.includes("'stamp.first_train_ticket'") && action.includes("'madrid.final.preview'"), 'Stationsbeloningen of de slotmissievooruitblik ontbreken');
assert(progressSnapshot.includes('STATION_MISSION_KEY') && progress.includes('stationProgress'), 'Het accountdashboard toont de stationsvoortgang niet');
assert(templates.includes("'station-nuria'") && readiness.includes("'station_text_dialogue'"), 'Content Studio mist de stationstarter of runtimecontrole');
assert(rule.includes('PlayableContentInspector') && inspector.includes("'station_text_dialogue'") && docs.includes('publiceert niets automatisch'), 'De redactionele contract- of publicatiegrens ontbreekt');

console.log('Stationmissie geldig: vijf actieve beurten, drie niveaupaden, Nuria als aanpasbare werk-NPC, privéproductiecontent, visuele stationsscène en duurzame dag-6-beloningen.');
