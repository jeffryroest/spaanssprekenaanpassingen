import { access, readFile, stat } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'public/images/game/madrid-morning.webp',
  'app/ContentStudio/PlayableContentTemplates.php',
  'app/ContentStudio/RuntimeReadiness.php',
  'app/Rules/PlayableDomainData.php',
  'resources/views/welcome.blade.php',
  'resources/views/game/madrid.blade.php',
  'resources/views/game/panaderia.blade.php',
  'resources/views/content-studio/content/_form.blade.php',
  'resources/js/game/madrid-hub.js',
  'resources/css/app.css',
  'scripts/deploy-production.sh',
  'scripts/verify-built-assets.mjs',
];

await Promise.all(paths.map((path) => access(new URL(path, root))));

const [home, hubView, bakeryView, hubScript, form, css, deploy, imageStats] = await Promise.all([
  read(paths[4]),
  read(paths[5]),
  read(paths[6]),
  read(paths[8]),
  read(paths[7]),
  read(paths[9]),
  read(paths[10]),
  stat(new URL(paths[0], root)),
]);

assert(home.includes('Start je eerste missie'), 'De homepage mist een spelersgerichte primaire actie');
assert(!home.includes('Laravel 13') && !home.includes('Fase 3B1'), 'De publieke homepage bevat nog technische fasejargon');
assert(home.includes('madrid-morning.webp'), 'De homepage gebruikt de Madrid-wereldillustratie niet');
assert(imageStats.size < 400_000, 'De Madrid-wereldillustratie is te groot voor de eerste paginalaad');
assert(hubView.includes('data-hub-arrival') && hubView.includes('data-hub-preparation'), 'Aankomst of missievoorbereiding ontbreekt');
assert(hubScript.includes('madrid-mission-preparation'), 'De boodschappenkaart wordt niet veilig tussen wereld en scène doorgegeven');
assert(hubScript.includes('readDialogueCompletion'), 'De wereld reageert niet op een lokaal voltooide missie');
assert(bakeryView.includes('data-preparation-summary'), 'De bakkerij toont de voorbereide boodschappenkaart niet');
assert(form.includes('name="domain_data"') && form.includes('Start met speelbare content'), 'Content Studio kan geen speeldata of starter invoeren');
assert(css.includes("url('/images/game/madrid-morning.webp')"), 'De visuele wereldlaag is niet met de hub verbonden');
assert(deploy.includes('npm ci') && deploy.includes('verify-built-assets.mjs'), 'Het productiedeployscript borgt geen reproduceerbare, gecontroleerde Vite-build');

console.log('Fase 3B1.5 geldig: speeldata-invoer, spelershome, visuele Madrid-laag, voorbereiding, wereldreactie en buildborging zijn aanwezig.');
