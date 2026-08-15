import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'content/examples/madrid-hub-domain-data.json',
  'docs/contracts/madrid-hub-v1.schema.json',
  'docs/madrid-hub.md',
  'resources/js/game/madrid-hub.js',
  'resources/views/game/madrid.blade.php',
];

await Promise.all(paths.map((path) => access(new URL(path, root))));

const [exampleSource, schemaSource, script, view, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[3]), read(paths[4]), read('routes/web.php'),
]);
const example = JSON.parse(exampleSource);
const schema = JSON.parse(schemaSource);

assert(example.schema_version === '1.0.0', 'Het Madrid-hubvoorbeeld moet contractversie 1.0.0 gebruiken');
assert(example.scene === 'madrid_hub', 'Het Madrid-hubvoorbeeld moet scene madrid_hub gebruiken');
assert(example.hotspots.length >= 4, 'De hub moet minimaal vier hotspots bevatten');
assert(example.hotspots.filter(({ state }) => state === 'open').length === 1, 'Exact één hotspot moet in fase 2A open zijn');
assert(example.inspectables.length >= 3, 'De hub moet minimaal drie onderzoekspunten bevatten');
assert(example.inspectables.every(({ reward }) => reward.curiosidad === 1), 'Ieder onderzoekspunt levert één Curiosidad op');
assert(new Set(example.hotspots.map(({ id }) => id)).size === example.hotspots.length, 'Hotspot-id’s moeten uniek zijn');
assert(schema.properties?.scene?.const === 'madrid_hub', 'Het JSON Schema moet de Madrid-scène begrenzen');
assert(routes.includes("Route::view('/spelen/madrid'"), 'De Madrid-hubroute ontbreekt');
assert(view.includes('/api/v1/worlds/madrid?locale=nl-NL'), 'De hub moet de publieke productie-API gebruiken');
assert(!view.includes('madrid-hub-domain-data.json'), 'De runtime mag voorbeeldcontent niet rechtstreeks tonen');
assert(script.includes('credentials: \'same-origin\''), 'De runtimefetch moet dezelfde origin gebruiken');
assert(script.includes('textContent'), 'Dynamische content moet veilig via textContent worden opgebouwd');
assert(view.includes('data-hub-list-view'), 'De semantische lijstweergave ontbreekt');
assert(view.includes('data-hub-sound'), 'De stille-modusbediening ontbreekt');

console.log('Madrid-hub geldig: productie-API, vier hotspots, drie onderzoekspunten en toegankelijke alternatieven zijn aanwezig.');
