import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

async function readJson(relativePath) {
  return JSON.parse(await readFile(new URL(relativePath, root), 'utf8'));
}

const requiredPaths = [
  'app/Http/Controllers/Controller.php',
  'artisan',
  'bootstrap/app.php',
  'composer.json',
  'config/app.php',
  'public/index.php',
  'resources/views/welcome.blade.php',
  'routes/web.php',
  'tests/Feature/HomePageTest.php',
  'vite.config.js',
];

await Promise.all(requiredPaths.map((relativePath) => access(new URL(relativePath, root))));

const composer = await readJson('composer.json');
const packageJson = await readJson('package.json');
const envExample = await readFile(new URL('.env.example', root), 'utf8');
const homepage = await readFile(new URL('resources/views/welcome.blade.php', root), 'utf8');

assert(composer.require?.php === '^8.4', 'Composer moet PHP 8.4 vereisen');
assert(composer.require?.['laravel/framework']?.startsWith('^13.'), 'Laravel 13 moet vastgelegd zijn');
assert(packageJson.scripts?.build === 'vite build', 'De Vite-build ontbreekt');
assert(packageJson.devDependencies?.tailwindcss?.startsWith('^4.'), 'Tailwind CSS 4 moet vastgelegd zijn');
assert(envExample.includes('DB_CONNECTION=mysql'), 'De voorbeeldomgeving moet MySQL gebruiken');
assert(envExample.includes('APP_LOCALE=nl'), 'De standaardlocale moet Nederlands zijn');
assert(homepage.includes('La panadería') && homepage.includes('Start je eerste missie'), 'De eerste vertical slice ontbreekt op de startpagina');

console.log('Laravel 13-fundament geldig: structuur, runtime en startpagina zijn consistent.');
