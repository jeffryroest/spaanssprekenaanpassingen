import { access, readFile, stat } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const playerViews = [
  'resources/views/welcome.blade.php',
  'resources/views/player/account.blade.php',
  'resources/views/player/progress.blade.php',
  'resources/views/player/trial-week.blade.php',
  'resources/views/game/madrid.blade.php',
  'resources/views/game/panaderia.blade.php',
  'resources/views/game/taxi.blade.php',
  'resources/views/game/restaurant.blade.php',
  'resources/views/game/review.blade.php',
  'resources/views/game/health.blade.php',
  'resources/views/game/station.blade.php',
  'resources/views/game/final.blade.php',
  'resources/views/privacy.blade.php',
  'resources/views/billing/order.blade.php',
];
const media = [
  'madrid-taxi-interior.webp',
  'diego-taxi-expressions.webp',
  'cafe-el-reloj-interior.webp',
  'carmen-restaurant-expressions.webp',
  'consulta-la-luz-interior.webp',
  'elena-doctor-expressions.webp',
];

await Promise.all([
  ...playerViews.map((path) => access(new URL(path, root))),
  ...media.flatMap((filename) => [
    access(new URL(`resources/game-assets/golden-route/${filename}`, root)),
    access(new URL(`public/images/game/${filename}`, root)),
  ]),
]);

const [views, navigation, header, manifest, readiness, betaView, routes, commands, publisher, publishedUpgrade, tests] = await Promise.all([
  Promise.all(playerViews.map(read)),
  read('resources/views/components/player/navigation.blade.php'),
  read('resources/views/components/player/header.blade.php'),
  read('app/ContentStudio/GoldenRouteMedia.php'),
  read('app/ContentStudio/RuntimeReadiness.php'),
  read('resources/views/content-studio/beta/index.blade.php'),
  read('routes/web.php'),
  read('routes/console.php'),
  read('app/ContentStudio/TrialWeekContentRelease.php'),
  read('app/ContentStudio/PreparePublishedDemoMediaUpgrade.php'),
  read('tests/Feature/TrialWeekContentReleaseTest.php'),
]);

assert(views.every((view) => view.includes('<x-player.header')), 'Niet iedere spelerspagina gebruikt de gedeelde header');
for (const label of ['Madrid', 'Mijn proefweek', 'Voortgang', 'Account', 'Uitloggen']) {
  assert(navigation.includes(label), `De gedeelde navigatie mist ${label}`);
}
assert(header.includes('player-nav-desktop') && header.includes('player-nav-mobile'), 'De gedeelde header mist een toegankelijke desktop- of mobiele variant');
assert(media.every((filename) => manifest.includes(filename)), 'Niet alle nieuwe proefweekmedia staan in het beheerde manifest');

const mediaSizes = await Promise.all(media.map((filename) => stat(new URL(`resources/game-assets/golden-route/${filename}`, root))));
assert(mediaSizes.every(({ size }) => size > 30_000), 'Een gegenereerd proefweekbeeld lijkt leeg of onvolledig');
assert(readiness.includes('Persoonlijke herhaling') && readiness.includes("requiredMediaRoles: ['scene_background', 'npc_expression_sheet']"), 'De dagmatrix controleert niet alle dynamische content en mediarollen');
assert(betaView.includes('Proefweekcontent en media') && betaView.includes("operations['content_items']"), 'De Content Studio toont geen uitvoerbare proefweekmatrix');
for (const mission of ['taxi', 'restaurant', 'health']) {
  assert(routes.includes(`game.madrid.${mission}.media`), `De afgeschermde mediaroute voor ${mission} ontbreekt`);
}
assert(commands.includes('game:publish-trial-week-content') && commands.includes('--confirm=PUBLICEREN'), 'Het gecontroleerde publicatiecommando ontbreekt');
assert(publisher.includes('independent') || publisher.includes('onafhankelijke reviewer'), 'De vier-ogencontrole ontbreekt in de publicatieservice');
assert(publishedUpgrade.includes('transactionLevel()') && publishedUpgrade.includes('content.published_demo_media_upgrade_prepared'), 'De atomaire upgrade van eerder gepubliceerde pakketcontent ontbreekt');
assert(tests.includes('installs_reviews_and_publishes') && tests.includes('independent_reviewer'), 'De publicatieflow mist regressiedekking');

console.log('Fase 4B3 geldig: complete proefweekmedia, dagmatrix, vier-ogenpublicatie en één spelersinterface zijn geborgd.');
