import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteFinalMission.php',
  'app/ContentStudio/PlayableContentInspector.php',
  'app/PlayerProgress/NpcMemorySnapshot.php',
  'content/examples/final-dialogue-domain-data.json',
  'docs/contracts/final-dialogue-v1.schema.json',
  'docs/final-mission.md',
  'resources/views/game/final.blade.php',
  'tests/Feature/FinalMissionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, inspector, memory, exampleSource, schemaSource, docs, view, tests, routes, catalog, media, builder, preview, progressSchema] = await Promise.all([
  ...paths.map(read),
  read('routes/web.php'),
  read('app/Access/TrialWeekCatalog.php'),
  read('app/ContentStudio/GoldenRouteMedia.php'),
  read('resources/js/content-studio/content-builder.js'),
  read('resources/views/content-studio/previews/show.blade.php'),
  read('docs/contracts/player-progress-v1.schema.json'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const progress = JSON.parse(progressSchema);
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'final_text_dialogue' && example.mission.id === 'mission.madrid.week.final', 'De dag-7-identiteit ontbreekt');
assert(example.mission.required_text_turns === 5 && example.steps.length === 7 && stepIds.size === 7, 'De finale vereist vijf actieve beurten en zeven unieke contentstappen');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Niet ieder finaleniveau heeft een geldige complicatie');
assert(example.memory?.returning_npc_id === 'npc.lucia.martin' && example.memory?.recap_sources?.length === 5, 'Het structurele NPC-geheugencontract is onvolledig');
assert(schema.properties?.memory?.properties?.source_mission_key?.const === 'mission.madrid.panaderia.breakfast', 'Het JSON-schema begrenst de geheugenbron niet');
assert(progress.$defs?.mission?.properties?.key?.enum?.includes('mission.madrid.week.final'), 'Het voortgangscontract accepteert de finale niet');
assert(routes.includes("Route::get('/spelen/madrid/finale'") && routes.includes("Route::post('/spelen/madrid/finale/voltooien'"), 'De finaleroutes ontbreken');
assert(catalog.includes("'conversation_slug' => 'madrid-final-lucia'") && catalog.includes("'route' => 'game.madrid.final'"), 'Dag 7 controleert de publicatie niet');
assert(media.includes("'final' =>") && media.includes("'lucia_expressions'"), 'De finale hergebruikt de gouden-route-media niet');
assert(inspector.includes("'final_text_dialogue'") && inspector.includes('Terugblikbron'), 'Content Studio valideert het geheugencontract niet');
assert(builder.includes("data.scene === 'final_text_dialogue'") && builder.includes('NPC-herkenning'), 'De auteurvriendelijke geheugenbouwer ontbreekt');
assert(preview.includes("$domainData['scene'] === 'final_text_dialogue'") && preview.includes('memory.recap_sources'), 'De geheugenpreview ontbreekt');
assert(view.includes('data-memory-returning') && view.includes('Vrije antwoorden, transcripties, audio en feedback'), 'De speler ziet herkenning of privacygrens niet');
assert(memory.includes("where('status', 'completed')") && !memory.includes('MissionAttempt'), 'NPC-geheugen moet uitsluitend voltooide missiestatus lezen');
assert(action.includes("'stamp.madrid_week_complete'") && action.includes("'spain.next_city.preview'"), 'Finalebeloningen of vervolgontgrendeling ontbreken');
assert(tests.includes('persists_no_free_answers') && docs.includes('geen vrije antwoorden'), 'Privacyregressie of documentatie ontbreekt');

console.log('Dag 7 geldig: vijf actieve beurten, drie niveaus, private releasecontent, hergebruikte Lucía-media en minimaal structureel NPC-geheugen.');
