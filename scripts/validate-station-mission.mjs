import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteStationMission.php',
  'app/Access/TrialWeekCatalog.php',
  'app/ContentStudio/GoldenRouteMedia.php',
  'app/ContentStudio/PlayableContentTemplates.php',
  'app/ContentStudio/RuntimeReadiness.php',
  'app/Http/Controllers/Game/CompleteStationMissionController.php',
  'app/Http/Requests/Game/AssessTurnRequest.php',
  'app/Rules/PlayableDomainData.php',
  'content/examples/station-dialogue-domain-data.json',
  'docs/contracts/station-dialogue-v1.schema.json',
  'docs/station-mission.md',
  'public/images/game/madrid-station-hall.webp',
  'public/images/game/mateo-station-expressions.webp',
  'resources/game-assets/golden-route/madrid-station-hall.webp',
  'resources/game-assets/golden-route/mateo-station-expressions.webp',
  'resources/js/content-studio/content-builder.js',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/content-studio/previews/show.blade.php',
  'resources/views/game/station.blade.php',
  'resources/views/player/progress.blade.php',
  'tests/Feature/StationMissionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, catalog, media, templates, readiness, request, rule, exampleSource, schemaSource, docs, builder, dialogue, preview, view, progress, routes, css] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[4]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[15]), read(paths[16]), read(paths[17]), read(paths[18]), read(paths[19]), read('routes/web.php'), read('resources/css/app.css'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const progressSchema = JSON.parse(await read('docs/contracts/player-progress-v1.schema.json'));
const inspector = await read('app/ContentStudio/PlayableContentInspector.php');
const installer = await read('app/ContentStudio/DemoContentInstaller.php');
const contentController = await read('app/Http/Controllers/Game/EntitledConversationController.php');
const mediaController = await read('app/Http/Controllers/Game/EntitledConversationMediaController.php');
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'station_text_dialogue' && example.npc.id === 'npc.mateo.alvarez', 'De stationidentiteit of Mateo ontbreekt');
assert(example.mission.id === 'mission.madrid.station.ticket' && example.mission.required_text_turns === 5, 'De stationsmissie moet exact vijf actieve beurten hebben');
assert(example.journey?.fictional === true && example.journey.details?.length >= 3, 'De stationsmissie mist de fictieve oefenreis');
assert(example.steps.length === 7 && stepIds.size === 7, 'De stationsmissie moet zeven unieke contentstappen hebben');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau mist een stationscomplicatie');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere stationsbeurt moet optionele voorbeeldsteun bieden');
assert(schema.properties?.scene?.const === 'station_text_dialogue' && schema.properties?.journey?.properties?.fictional?.const === true, 'Het stationscontract begrenst scène of oefenreis niet');
assert(progressSchema.$defs?.mission?.properties?.key?.enum?.includes('mission.madrid.station.ticket'), 'Het voortgangscontract accepteert de stationsmissie niet');
assert(example.runtime_access?.visibility === 'entitled' && schema.properties?.runtime_access?.properties?.entitlement?.const === 'trial_week', 'Stationscontent moet afgeschermde proefweekcontent zijn');
assert(routes.includes("Route::view('/spelen/madrid/station'") && routes.includes("Route::post('/spelen/madrid/station/voltooien'") && routes.includes('entitled:trial_week'), 'De stationsroutes missen hun proefweekgrens');
assert(routes.includes("Route::get('/spelen/madrid/station/media/{version}/{role}'") && contentController.includes("$request->route('mediaRouteName')"), 'De privécontent koppelt stationsmedia niet aan de gepubliceerde revisie');
assert(mediaController.includes('allowsEntitlement') && mediaController.includes("'Cache-Control' => 'private, no-store'") && mediaController.includes('$releaseItem->version !== $version'), 'De stationsmedia mist recht-, cache- of versiegrenzen');
assert(catalog.includes("'conversation_slug' => 'estacion-mateo'") && catalog.includes("'route' => 'game.madrid.station'"), 'Dag 6 controleert de stationspublicatie niet');
assert(request.includes("'estacion-mateo'") && dialogue.includes('scenario_slug: scenarioSlug'), 'Stationsfeedback is niet aan de eigen scenarioslug gekoppeld');
assert(view.includes('data-station-dialogue') && view.includes('data-journey-card') && view.includes('data-npc-expression-sheet'), 'De visuele stationsclient of oefenreiskaart ontbreekt');
assert(view.includes("route('game.madrid.station.content'") && view.includes('WebM/Opus'), 'De privécontent- of spreekroute ontbreekt in de stationsscène');
assert(action.includes("'stamp.first_madrid_train_ticket'") && action.includes("'madrid.week_final.preview'"), 'Stationsbeloningen of de slotvooruitblik ontbreken');
assert(templates.includes("'estacion-mateo'") && readiness.includes("'station_text_dialogue'") && readiness.includes("'npc_expression_sheet'"), 'Content Studio mist de stationsstarter of mediacontrole');
assert(rule.includes('PlayableContentInspector') && inspector.includes("'station_text_dialogue'") && builder.includes('Fictieve oefenreis') && preview.includes('journey.details'), 'De auteurvriendelijke stationsbouwer of preview ontbreekt');
assert(media.includes("'madrid_station_hall'") && media.includes("'mateo_station_expressions'") && installer.includes('PACKAGE_VERSION'), 'Het demopakket mist de versiegebonden stationsmedia');
assert(progress.includes('stationProgress') && css.includes('.station-journey-card') && css.includes('.station-mateo-frame'), 'Voortgang of visuele stationsstijl ontbreekt');
assert(docs.includes('nooit automatisch') && docs.includes('geen actuele dienstregeling'), 'De redactionele of fictieve reisgrens ontbreekt in de documentatie');

console.log('Stationsmissie geldig: fictieve oefenreis, vijf actieve beurten, drie niveaupaden, visuele Mateo-scène, privéproductiecontent en duurzame dag-6-beloningen.');
