import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

async function read(relativePath) {
  return readFile(new URL(relativePath, root), 'utf8');
}

const requiredPaths = [
  'app/ContentApi/PublicApiResponder.php',
  'app/ContentApi/PublicContentTransformer.php',
  'app/ContentApi/PublishedContentRepository.php',
  'app/Http/Controllers/Api/V1/PublishedContentController.php',
  'app/Http/Controllers/Api/V1/PublishedMediaController.php',
  'docs/contracts/public-content-api-v1.schema.json',
  'docs/public-content-api.md',
  'routes/api.php',
  'tests/Feature/PublicContentApiTest.php',
];

await Promise.all(requiredPaths.map((path) => access(new URL(path, root))));

const [schemaSource, routes, bootstrap, repository, responder, tests] = await Promise.all([
  read('docs/contracts/public-content-api-v1.schema.json'),
  read('routes/api.php'),
  read('bootstrap/app.php'),
  read('app/ContentApi/PublishedContentRepository.php'),
  read('app/ContentApi/PublicApiResponder.php'),
  read('tests/Feature/PublicContentApiTest.php'),
]);
const schema = JSON.parse(schemaSource);
const version = responder.match(/API_VERSION\s*=\s*'([^']+)'/)?.[1];

assert(schema.$schema === 'https://json-schema.org/draft/2020-12/schema', 'Het API-contract moet JSON Schema 2020-12 gebruiken');
assert(version === '1.0.0', 'De publieke API-versie moet expliciet 1.0.0 zijn');
assert(schema.$defs?.detailResponse?.properties?.schema_version?.const === version, 'Detailcontract en runtimeversie wijken af');
assert(schema.$defs?.collectionResponse?.properties?.schema_version?.const === version, 'Collectiecontract en runtimeversie wijken af');
assert(schema.$defs?.errorResponse?.properties?.schema_version?.const === version, 'Foutcontract en runtimeversie wijken af');

for (const path of [
  '/worlds',
  '/worlds/{slug}',
  '/locations',
  '/locations/{slug}',
  '/missions',
  '/missions/{slug}',
  '/conversations',
  '/conversations/{slug}',
]) {
  assert(routes.includes(`'${path}'`), `Publieke API-route ${path} ontbreekt`);
}

assert(routes.includes("middleware('throttle:120,1')"), 'De publieke API-rate limit ontbreekt');
assert(bootstrap.includes("api: __DIR__.'/../routes/api.php'"), 'routes/api.php is niet geregistreerd');
assert(repository.includes('ContentReleaseChannel::Production'), 'De API begrenst niet op het productiekanaal');
assert(repository.includes('ContentReleaseStatus::Published'), 'De API begrenst niet op uitgevoerde releases');
assert(repository.includes("whereColumn('content_release_items.version', 'content_nodes.current_version')"), 'De API begrenst niet op de actuele exacte revisie');
assert(repository.includes("whereColumn('content_revisions.content_node_id', 'content_release_items.content_node_id')"), 'De API controleert niet of de revisie bij hetzelfde contentobject hoort');
assert(repository.includes('runtime_access->visibility') && repository.includes('paginatePublic'), 'De publieke API moet accountgebonden runtimecontent uitsluiten');
assert(tests.includes('test_only_exact_current_production_publications_are_listed'), 'De fail-closed publicatiegrens mist een featuretest');
assert(tests.includes('test_entitled_content_is_not_exposed_by_the_public_api'), 'De publieke API mist een regressietest voor accountgebonden content');
assert(tests.includes('test_detail_response_supports_conditional_gets'), 'ETag-gedrag mist een featuretest');
assert(routes.includes("'/media/{contentType}/{slug}/{version}/{role}'"), 'De revisiegebonden publieke mediaroute ontbreekt');
assert(tests.includes('exact_published_revision'), 'De publieke mediaroute mist een regressietest voor revisie, rol en caching');

console.log('Publieke content-API v1 geldig: routes, contract, publicatiegrens, revisiemedia en tests zijn aanwezig.');
