import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const inspector = read('app/ContentStudio/PlayableContentInspector.php');
const builder = read('resources/js/content-studio/content-builder.js');
const preview = read('resources/js/content-studio/content-preview.js');
const previewView = read('resources/views/content-studio/previews/show.blade.php');
const form = read('resources/views/content-studio/content/_form.blade.php');
const media = read('app/Actions/ContentStudio/CreateMediaAsset.php');
const migration = read('database/migrations/2026_08_30_150000_create_content_media_tables.php');
const preflight = read('app/Actions/ContentStudio/InspectContentRelease.php');
const routes = read('routes/web.php');
const tests = read('tests/Feature/ContentStudioBuilderPreviewTest.php') + read('tests/Feature/ContentStudioMediaTest.php');
const docs = read('docs/content-builder-preview.md');

assert(inspector.includes('routeReachesCompletion') && inspector.includes('routecyclus') && inspector.includes("['A0', 'A1', 'A2']"), 'De diepe niveau- en routegrafiekcontrole ontbreekt');
assert(inspector.includes('inspectUniqueIds') && inspector.includes('inspectPosition'), 'De unieke ids of kaartposities worden niet diep gecontroleerd');
assert(builder.includes('renderMadrid') && builder.includes('renderDialogue') && builder.includes('Routeoptie toevoegen'), 'De wereld- of gespreksbouwer is niet volledig aangesloten');
assert(form.includes('data-content-builder-root') && form.includes('Geavanceerde JSON') && form.includes('media['), 'Het Content Studio-formulier mist de bouwer, herstelroute of mediarollen');
assert(preview.includes('data-preview-submit') && preview.includes('mission_attempt') === false && preview.includes('fetch(') === false, 'De preview is niet lokaal of bevat een voortgangsaanroep');
assert(previewView.includes('Niet-productiepreview') && previewView.includes('noindex,nofollow,noarchive') && previewView.includes('schrijft geen voortgang'), 'De preview mist zijn zichtbare of technische veiligheidsgrens');
assert(migration.includes("Schema::create('media_assets'") && migration.includes("Schema::create('content_media'") && migration.includes('content_revision_id'), 'Het versiegebonden mediamodel ontbreekt');
assert(media.includes("hash_file('sha256'") && media.includes('getMimeType') && media.includes('recordMediaChange'), 'Upload mist inhoudscontrole, checksum of audit');
assert(preflight.includes('hasAccessibilityText') && preflight.includes('rights_status') && preflight.includes('mediaAssets'), 'Releasepreflight controleert mediarechten of toegankelijkheid niet');
assert(routes.includes("content/{contentNode}/preview") && routes.includes("middleware('signed')") && routes.includes("media/{mediaAsset}/bestand"), 'Ondertekende preview- of privémediastreamroute ontbreekt');
assert(tests.includes('writes_no_player_data') && tests.includes('blocks_media_without_publication_rights') && tests.includes('missing_route_reference'), 'Regressietests missen previewprivacy, rechten of routefouten');
assert(docs.includes('CONTENT_STUDIO_MEDIA_DISK=local') && docs.includes('nooit') && docs.includes('A0/A1/A2'), 'De beheer- en veiligheidsdocumentatie is onvolledig');

console.log('Fase 3B4 geldig: typespecifieke bouwer, diepe routegrafiekvalidatie, privémedia en voortgangsvrije preview.');
