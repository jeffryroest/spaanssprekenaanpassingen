const baseUrl = process.argv[2];

if (!baseUrl || !/^https:\/\//.test(baseUrl)) {
  throw new Error('Gebruik: node scripts/smoke-live-frontend.mjs https://jouwdomein.nl');
}

const response = await fetch(new URL('/', baseUrl), {
  headers: { Accept: 'text/html', 'User-Agent': 'Spaansspreken deploy smoke test' },
});

if (!response.ok) {
  throw new Error(`De startpagina antwoordt met HTTP ${response.status}.`);
}

const html = await response.text();
const cssPath = html.match(/href="([^"]*\/build\/assets\/[^"?]+\.css)"/)?.[1];
const jsPath = html.match(/src="([^"]*\/build\/assets\/[^"?]+\.js)"/)?.[1];

if (!cssPath || !jsPath) {
  throw new Error('De live HTML verwijst niet naar de verwachte Vite CSS en JavaScript.');
}

const [cssResponse, jsResponse, hubResponse, sceneResponse, luciaResponse, stationSceneResponse, mateoResponse] = await Promise.all([
  fetch(new URL(cssPath, baseUrl)),
  fetch(new URL(jsPath, baseUrl)),
  fetch(new URL('/spelen/madrid', baseUrl)),
  fetch(new URL('/images/game/la-espiga-interior.webp', baseUrl)),
  fetch(new URL('/images/game/lucia-expressions.webp', baseUrl)),
  fetch(new URL('/images/game/madrid-station-hall.webp', baseUrl)),
  fetch(new URL('/images/game/mateo-station-expressions.webp', baseUrl)),
]);
const [css, js] = await Promise.all([cssResponse.text(), jsResponse.text()]);

if (!cssResponse.ok || !css.includes('.world-home-body') || !css.includes('.hub-world-return') || !css.includes('.bakery-lucia-frame') || !css.includes('.station-journey-card') || !css.includes('.review-stage')) {
  throw new Error('De live CSS is verouderd of onvolledig.');
}

if (!jsResponse.ok || !js.includes('madrid-mission-preparation') || !js.includes('npc_expression_sheet') || !js.includes('data-journey-card') || !js.includes('data-review-rating') || !js.includes('la-espiga-complete')) {
  throw new Error('De live JavaScript is verouderd of onvolledig.');
}

if (!hubResponse.ok || !(await hubResponse.text()).includes('data-golden-route-version="3B5"')) {
  throw new Error('De live Madrid-route gebruikt niet de gouden-route-template van fase 3B5.');
}

if (!sceneResponse.ok || !luciaResponse.ok || !stationSceneResponse.ok || !mateoResponse.ok) {
  throw new Error('Een meegeleverde gouden-route- of stationsafbeelding ontbreekt op de live site.');
}

console.log(`Live frontend geldig: ${new URL(cssPath, baseUrl)} en ${new URL(jsPath, baseUrl)} zijn actueel.`);
