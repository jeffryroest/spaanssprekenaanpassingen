import { readFileSync, statSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const size = (path) => statSync(new URL(`../${path}`, import.meta.url)).size;
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const hubView = read('resources/views/game/madrid.blade.php');
const bakeryView = read('resources/views/game/panaderia.blade.php');
const hubRuntime = read('resources/js/game/madrid-hub.js');
const dialogueRuntime = read('resources/js/game/panaderia-dialogue.js');
const styles = read('resources/css/app.css');
const transformer = read('app/ContentApi/PublicContentTransformer.php');
const mediaController = read('app/Http/Controllers/Api/V1/PublishedMediaController.php');
const installer = read('app/ContentStudio/DemoContentInstaller.php');
const visualMedia = read('app/ContentStudio/GoldenRouteMedia.php');
const readiness = read('app/ContentStudio/RuntimeReadiness.php');
const apiTests = read('tests/Feature/PublicContentApiTest.php');
const demoTests = read('tests/Feature/DemoContentInstallerTest.php');
const docs = read('docs/visual-golden-route.md');

for (const asset of [
  'public/images/game/la-espiga-interior.webp',
  'public/images/game/lucia-expressions.webp',
  'resources/game-assets/golden-route/madrid-morning.webp',
  'resources/game-assets/golden-route/la-espiga-interior.webp',
  'resources/game-assets/golden-route/lucia-expressions.webp',
]) {
  assert(size(asset) > 20_000, `Visuele gouden-route-asset ontbreekt of is verdacht klein: ${asset}`);
}

assert(hubView.includes('data-hub-world-return') && hubView.includes('data-golden-route-version="3B5"'), 'Madrid mist de zichtbare voltooiingsreactie of fasemarkering');
assert(bakeryView.includes('data-lucia-expression-sheet') && bakeryView.includes('bakery-world-reaction'), 'La Espiga mist Lucía-reacties of de wereldbeloning');
assert(hubRuntime.includes('applyRuntimeMedia') && hubRuntime.includes('la-espiga-complete'), 'Madrid gebruikt gepubliceerde media of de terugkeerlus niet');
assert(dialogueRuntime.includes('npc_expression_sheet') && dialogueRuntime.includes("setNpcState('success')"), 'De dialoog koppelt media en NPC-reacties niet aan spelstatus');
assert(styles.includes('.bakery-lucia-frame') && styles.includes('.hub-world-return') && styles.includes('prefers-reduced-motion'), 'De visuele en bewegingsarme stijlen zijn onvolledig');
assert(transformer.includes("'media' =>") && transformer.includes("route('api.v1.media.show'"), 'De publieke contentruntime adverteert geen revisiegebonden media');
assert(mediaController.includes('max-age=31536000') && mediaController.includes('isPublishable') && mediaController.includes('If-None-Match'), 'De mediastream mist publicatiegrens, immutable caching of ETag');
assert(installer.includes("'upgrade'") && visualMedia.includes('npc_expression_sheet'), 'Het demopakket kan oude concepten of de reactiemedia niet veilig installeren');
assert(readiness.includes('missing_media_roles') && readiness.includes("['scene_background', 'npc_expression_sheet']"), 'Het productiedashboard controleert de gouden-route-media niet');
assert(apiTests.includes('exact_published_revision') && demoTests.includes('older_madrid_demo'), 'De media- of upgradegrens mist regressietests');
assert(docs.includes('publiceert niets automatisch') && docs.includes('360 px'), 'De gouden-route-documentatie mist de menselijke of toegankelijke grens');

console.log('Fase 3B5 geldig: gereviewde runtime-media, drie Lucía-reacties, tastbare beloning en veranderde Madrid-wereld.');
