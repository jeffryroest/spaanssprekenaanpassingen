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

const [cssResponse, jsResponse] = await Promise.all([
  fetch(new URL(cssPath, baseUrl)),
  fetch(new URL(jsPath, baseUrl)),
]);
const [css, js] = await Promise.all([cssResponse.text(), jsResponse.text()]);

if (!cssResponse.ok || !css.includes('.world-home-body') || !css.includes('.hub-preparation-dialog')) {
  throw new Error('De live CSS is verouderd of onvolledig.');
}

if (!jsResponse.ok || !js.includes('madrid-mission-preparation')) {
  throw new Error('De live JavaScript is verouderd of onvolledig.');
}

console.log(`Live frontend geldig: ${new URL(cssPath, baseUrl)} en ${new URL(jsPath, baseUrl)} zijn actueel.`);
