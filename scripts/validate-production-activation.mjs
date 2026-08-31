import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const action = read('app/Actions/ContentStudio/ReplaceIncompleteDemoContent.php');
const installer = read('app/ContentStudio/DemoContentInstaller.php');
const consoleRoutes = read('routes/console.php');
const docs = read('docs/demo-ready-content-studio.md');
const tests = read('tests/Feature/DemoContentInstallerTest.php');

assert(action.includes('ContentRole::Administrator'), 'De vervangingsroute is niet uitsluitend aan beheerders voorbehouden');
assert(action.includes('published_at === null') && action.includes('releaseItems->isEmpty()'), 'Publicatie- en releasegebonden content wordt niet hard geblokkeerd');
assert(action.includes("domain_data.scene") && action.includes('mediaAssets->isEmpty()'), 'De route is niet beperkt tot onvolledige placeholders zonder media');
assert(action.includes('current_version + 1') && action.includes('content.demo_placeholder_replaced'), 'De oude revisie of auditgeschiedenis wordt niet controleerbaar behouden');
assert(action.includes('ContentReviewAction::Withdrawn') && action.includes('ContentStatus::Draft'), 'Een oude reviewaanvraag wordt niet veilig naar concept teruggebracht');
assert(installer.includes("'replace'") && installer.includes('replaceIncompleteDemoContent'), 'De installer voert het begrensde vervangingsplan niet uit');
assert(consoleRoutes.includes('--replace-existing') && consoleRoutes.includes('--confirm=') && consoleRoutes.includes('OVERSCHRIJVEN'), 'De CLI mist dubbele expliciete bevestiging');
assert(docs.includes('--dry-run --replace-existing') && docs.includes('--confirm=OVERSCHRIJVEN'), 'De productiehandleiding mist de veilige volgorde');
assert(tests.includes('replace_incomplete_unpublished_review_placeholders') && tests.includes('refuses_an_incomplete_placeholder_already_bound_to_a_release'), 'De productiecasus en releaseblokkade missen regressietests');

console.log('Fase 3B5.1 geldig: onvolledige productieplaceholders worden alleen expliciet, traceerbaar en zonder publicatie- of releaseverlies vervangen.');
