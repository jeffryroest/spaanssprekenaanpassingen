import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const manifestUrl = new URL('public/build/manifest.json', root);

await access(manifestUrl);

const manifest = JSON.parse(await readFile(manifestUrl, 'utf8'));
const cssEntry = manifest['resources/css/app.css'];
const jsEntry = manifest['resources/js/app.js'];

if (!cssEntry?.file || !jsEntry?.file) {
  throw new Error('De Vite-manifest mist de CSS- of JavaScript-entry van de applicatie.');
}

const css = await readFile(new URL(`public/build/${cssEntry.file}`, root), 'utf8');
const js = await readFile(new URL(`public/build/${jsEntry.file}`, root), 'utf8');

const checks = [
  [css.includes('.world-home-body'), 'De spelersgerichte homepagestijlen ontbreken in de productie-CSS.'],
  [css.includes('.hub-preparation-dialog'), 'De Madrid-voorbereidingslaag ontbreekt in de productie-CSS.'],
  [css.includes('.bakery-body'), 'De dialoogstijlen ontbreken in de productie-CSS.'],
  [css.includes('.health-role-card'), 'De fictieve consulta- en rolkaartstijlen ontbreken in de productie-CSS.'],
  [css.includes('.hub-world-return'), 'De zichtbare Madrid-wereldreactie ontbreekt in de productie-CSS.'],
  [css.includes('.bakery-lucia-frame'), 'De visuele Lucía-reacties ontbreken in de productie-CSS.'],
  [js.includes('madrid-mission-preparation'), 'De Madrid-voorbereidingslogica ontbreekt in de productie-JavaScript.'],
  [js.includes('data-scenario-dialogue'), 'De dialoogmotor ontbreekt in de productie-JavaScript.'],
  [js.includes('Antwoordtekst niet in browseropslag bewaard'), 'De gevoelige lokale tekstredactie ontbreekt in de productie-JavaScript.'],
  [js.includes('data-content-builder-source'), 'De speelcontentbouwer ontbreekt in de productie-JavaScript.'],
  [js.includes('data-preview-submit'), 'De voortgangsvrije preview ontbreekt in de productie-JavaScript.'],
  [js.includes('npc_expression_sheet'), 'De revisiegebonden personagemedia ontbreken in de productie-JavaScript.'],
  [js.includes('la-espiga-complete'), 'De gouden-route-terugkeer ontbreekt in de productie-JavaScript.'],
];

for (const [passes, message] of checks) {
  if (!passes) throw new Error(message);
}

console.log(`Productie-assets geldig: ${cssEntry.file} en ${jsEntry.file} bevatten de speelbare gouden route van fase 3B5.`);
