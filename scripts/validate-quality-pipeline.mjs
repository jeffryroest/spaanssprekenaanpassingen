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
  '.github/workflows/quality.yml',
  'docs/quality-pipeline.md',
  'package-lock.json',
];

await Promise.all(requiredPaths.map((path) => access(new URL(path, root))));

const [workflow, packageJsonSource, packageLockSource] = await Promise.all([
  read('.github/workflows/quality.yml'),
  read('package.json'),
  read('package-lock.json'),
]);
const packageJson = JSON.parse(packageJsonSource);
const packageLock = JSON.parse(packageLockSource);

assert(workflow.includes('pull_request:'), 'De kwaliteitsstraat moet op pull requests draaien');
assert(workflow.includes('push:'), 'De kwaliteitsstraat moet na samenvoegen opnieuw draaien');
assert(workflow.includes('workflow_dispatch:'), 'De kwaliteitsstraat moet handmatig uitvoerbaar zijn');
assert(workflow.includes('permissions:\n  contents: read'), 'De workflow moet minimale leesrechten gebruiken');
assert(workflow.includes('cancel-in-progress: true'), 'Verouderde runs moeten worden geannuleerd');
assert(! workflow.includes('pull_request_target:'), 'Onvertrouwde PR-code mag niet via pull_request_target draaien');

for (const job of ['php-quality:', 'frontend-quality:', 'laravel-tests:']) {
  assert(workflow.includes(job), `Workflowjob ${job} ontbreekt`);
}

for (const action of [
  'actions/checkout@v7',
  'actions/cache@v6',
  'actions/setup-node@v7',
  'shivammathur/setup-php@v2',
]) {
  assert(workflow.includes(action), `Actuele Action ${action} ontbreekt`);
}

for (const command of [
  'composer validate --strict',
  'vendor/bin/pint --test',
  'composer audit --locked',
  'npm ci --ignore-scripts',
  'npm run validate',
  'npm run build',
  'npm audit --audit-level=high',
  'php artisan migrate --force --no-interaction',
  'php artisan test --colors=always',
]) {
  assert(workflow.includes(command), `Kwaliteitsstap ${command} ontbreekt`);
}

assert(workflow.includes('image: mysql:8.4'), 'Laravel-tests moeten tegen MySQL 8.4 draaien');
assert(workflow.includes('persist-credentials: false'), 'Checkoutcredentials mogen niet onnodig blijven staan');
assert(packageLock.lockfileVersion === 3, 'package-lock.json moet lockfileVersion 3 gebruiken');

for (const dependencyGroup of ['devDependencies', 'optionalDependencies']) {
  for (const [dependency, version] of Object.entries(packageJson[dependencyGroup] ?? {})) {
    assert(
      packageLock.packages?.['']?.[dependencyGroup]?.[dependency] === version,
      `package-lock.json loopt niet gelijk voor ${dependency}`,
    );
  }
}

console.log('Kwaliteitsstraat geldig: triggers, rechten, jobs, audits, MySQL-tests en npm-lock zijn consistent.');
