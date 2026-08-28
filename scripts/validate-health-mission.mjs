import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompleteHealthMission.php',
  'app/Access/TrialWeekCatalog.php',
  'app/ContentStudio/PlayableContentTemplates.php',
  'app/ContentStudio/RuntimeReadiness.php',
  'app/Feedback/OpenAiTurnAssessor.php',
  'app/Http/Controllers/Game/CompleteHealthMissionController.php',
  'app/Http/Requests/Game/AssessTurnRequest.php',
  'app/Rules/PlayableDomainData.php',
  'content/examples/health-dialogue-domain-data.json',
  'content/examples/madrid-hub-domain-data.json',
  'docs/contracts/health-dialogue-v1.schema.json',
  'docs/health-mission.md',
  'resources/js/game/madrid-hub.js',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/health.blade.php',
  'resources/views/game/madrid.blade.php',
  'resources/views/player/progress.blade.php',
  'tests/Feature/HealthMissionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [
  action, catalog, templates, readiness, assessor, request, rule, exampleSource, hubExampleSource,
  schemaSource, docs, hub, dialogue, view, hubView, progress, privacy, routes, tests,
] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[4]), read(paths[6]),
  read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read(paths[12]),
  read(paths[13]), read(paths[14]), read(paths[15]), read(paths[16]), read('resources/views/privacy.blade.php'),
  read('routes/web.php'), read(paths[17]),
]);
const example = JSON.parse(exampleSource);
const hubExample = JSON.parse(hubExampleSource);
const schema = JSON.parse(schemaSource);
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.scene === 'health_text_dialogue' && example.npc.id === 'npc.elena.ortiz', 'De consulta-identiteit of Elena ontbreekt');
assert(example.mission.id === 'mission.madrid.health.appointment' && example.mission.required_text_turns === 5, 'De gezondheidsmissie moet exact vijf actieve beurten hebben');
assert(example.steps.length === 7 && stepIds.size === 7, 'De gezondheidsmissie moet zeven unieke contentstappen hebben');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau mist een geldige consultvraag');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere consultbeurt moet optionele voorbeeldsteun bieden');
assert(example.roleplay?.fictional === true && example.roleplay.facts.length >= 4, 'Een vaste fictieve rolkaart is verplicht');
assert(/geen echte medische gegevens/i.test(example.roleplay.privacy_notice) && /geen medisch advies/i.test(example.roleplay.medical_disclaimer), 'Privacy- of medisch voorbehoud ontbreekt');
assert(schema.properties?.scene?.const === 'health_text_dialogue' && schema.properties?.roleplay?.properties?.fictional?.const === true, 'Het gezondheidscontract dwingt de fictieve rolkaart niet af');
assert(example.runtime_access?.visibility === 'entitled' && schema.properties?.runtime_access?.properties?.entitlement?.const === 'trial_week', 'Gezondheidscontent moet afgeschermde proefweekcontent zijn');
assert(routes.includes("Route::view('/spelen/madrid/gezondheid'") && routes.includes("Route::post('/spelen/madrid/gezondheid/voltooien'") && routes.includes('entitled:trial_week'), 'De gezondheidsroutes missen hun proefweekgrens');
assert(catalog.includes("'conversation_slug' => 'consulta-elena'") && catalog.includes("'route' => 'game.madrid.health'"), 'Dag 5 controleert de consultpublicatie niet');
assert(request.includes("'consulta-elena'") && dialogue.includes('scenario_slug: scenarioSlug'), 'Consultfeedback is niet aan de eigen scenarioslug gekoppeld');
assert(view.includes('data-sensitive-roleplay="true"') && view.includes('data-roleplay-card') && view.includes('sessionStorage'), 'De gevoelige frontendgrens of rolkaart ontbreekt');
assert(dialogue.includes('redactDialogueText') && dialogue.includes('player: _player') && dialogue.includes('npc: _npc'), 'Antwoordtekst wordt nog in browseropslag bewaard');
assert(privacy.includes('Fictief gezondheidsrollenspel') && privacy.includes('gezondheidsinformatie'), 'De privacy-uitleg beschrijft de gevoelige missiegrens niet');
assert(assessor.includes('geen medische beoordeling, diagnose of advies') && !assessor.includes('in een bakkerij.'), 'De taalbeoordelaar is niet veilig scenario-onafhankelijk');
assert(action.includes("'stamp.first_consulta_conversation'") && action.includes("'madrid.station.preview'"), 'Consultbeloningen of de stationsvooruitblik ontbreken');
assert(templates.includes("'consulta-elena'") && readiness.includes("'health_text_dialogue'") && readiness.includes("'madrid.consulta.luz'"), 'Content Studio mist de consultstarter, kaartlocatie of runtimecontrole');
assert(rule.includes("'health_text_dialogue'") && docs.includes('nooit automatisch'), 'De redactionele contract- of publicatiegrens ontbreekt');
assert(hubExample.hotspots.some(({ id, kind }) => id === 'madrid.consulta.luz' && kind === 'clinic'), 'Consulta La Luz ontbreekt in de Madrid-starter');
assert(hubView.includes('data-health-route') && hub.includes('healthDay?.action_url'), 'Consulta La Luz reageert niet op de gepubliceerde dag-5-missie');
assert(progress.includes('healthProgress') && tests.includes('health_data_persisted'), 'Accountvoortgang of de privacyregressietest ontbreekt');

console.log('Gezondheidsmissie geldig: fictieve rolkaart, vijf actieve beurten, drie niveaupaden, privécontent, lokale tekstredactie en veilige dag-5-beloningen.');
