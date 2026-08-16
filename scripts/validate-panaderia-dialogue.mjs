import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'content/examples/panaderia-dialogue-domain-data.json',
  'docs/contracts/panaderia-dialogue-v1.schema.json',
  'docs/panaderia-text-dialogue.md',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/panaderia.blade.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [exampleSource, schemaSource, script, view, hubScript, hubView, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[3]), read(paths[4]),
  read('resources/js/game/madrid-hub.js'), read('resources/views/game/madrid.blade.php'), read('routes/web.php'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);
const stepIds = new Set(example.steps.map(({ id }) => id));

assert(example.schema_version === '1.0.0', 'De La Espiga-dialoog moet contractversie 1.0.0 gebruiken');
assert(example.scene === 'panaderia_text_dialogue', 'De scène moet panaderia_text_dialogue zijn');
assert(example.npc.id === 'npc.lucia.martin', 'Lucía Martín moet de vaste NPC van deze slice zijn');
assert(example.mission.required_text_turns >= 3, 'De missie moet minimaal drie tekstbeurten bevatten');
assert(example.steps.length >= 7 && stepIds.size === example.steps.length, 'De dialoogstappen moeten volledig en uniek zijn');
assert(['A0', 'A1', 'A2'].every((level) => stepIds.has(example.level_branches[level])), 'Ieder niveau moet een complicatievariant hebben');
assert(new Set(Object.values(example.level_branches)).size === 3, 'De drie niveaus moeten verschillende complicaties gebruiken');
assert(example.repair.terms.length >= 4, 'De dialoog moet meerdere herstelzinnen herkennen');
assert(example.steps.every((step) => step.choices.length >= 2), 'Iedere beurt moet keuzehulp bieden');
assert(example.steps.every((step) => step.options.every(({ next }) =>
  ['@complication', '@complete'].includes(next) || stepIds.has(next))), 'Iedere vervolgroute moet geldig zijn');
assert(schema.properties?.scene?.const === 'panaderia_text_dialogue', 'Het schema moet de scènesoort begrenzen');
assert(routes.includes("Route::view('/spelen/madrid/la-panaderia'"), 'De bakkerijroute ontbreekt');
assert(view.includes('/api/v1/conversations/la-espiga-lucia?locale=nl-NL'), 'De dialoog moet de publieke productie-API gebruiken');
assert(view.includes('data-translation-toggle') && view.includes('data-dialogue-history'), 'Vertaling of gespreksverloop ontbreekt');
assert(script.includes('sessionStorage') && script.includes('textContent'), 'Veilig hervatten en veilige tekstweergave ontbreken');
assert(script.includes("normalize('NFD')"), 'Vrije invoer moet accenttolerant op intentietermen worden beoordeeld');
assert(!script.includes('MediaRecorder') && !script.includes('ffmpeg'), 'Spraakopname hoort niet in fase 2B');
assert(hubView.includes('data-panaderia-route'), 'De Madrid-hub mist de route naar La Espiga');
assert(hubScript.includes('window.location.assign'), 'De hubknop moet de bakkerij werkelijk openen');

console.log('La Espiga-dialoog geldig: vijf tekstbeurten, drie complicaties, herstelzinnen, hervatten en productiecontentgrens zijn aanwezig.');
