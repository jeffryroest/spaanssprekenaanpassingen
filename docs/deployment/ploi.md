# Deployment op Ploi

## Vereisten

- PHP 8.4 voor zowel de website als de CLI.
- Composer 2.
- Node.js en npm voor de Vite-build.
- MySQL-database met een afzonderlijke gebruiker.
- NGINX-documentroot: `/home/ploi/v2.spaansspreken.nl/public`.

Controleer vóór de eerste deployment:

```bash
php -v
composer --version
node --version
npm --version
```

`php -v` moet PHP 8.4 tonen. Het project weigert installatie onder een oudere PHP-versie via de platformeis in `composer.json`.

## Omgeving

Maak via Ploi een `.env` aan op basis van `.env.example`. Gebruik minimaal:

```dotenv
APP_NAME="Spaansspreken.nl"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://v2.spaansspreken.nl
APP_LOCALE=nl
APP_FALLBACK_LOCALE=nl

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<database>
DB_USERNAME=<gebruiker>
DB_PASSWORD=<sterk-wachtwoord>

TRANSCRIPTION_DRIVER=openai
OPENAI_API_KEY=<geheime-OpenAI-projectsleutel>
OPENAI_TRANSCRIPTION_MODEL=gpt-4o-mini-transcribe
```

Commit `.env` nooit en plaats `OPENAI_API_KEY` uitsluitend in de Ploi-omgeving. Genereer bij de eerste installatie de applicatiesleutel met `php artisan key:generate`.

## Deploymentscript

Gebruik in Ploi, nadat de repository naar de gewenste branch is uitgecheckt:

```bash
cd /home/ploi/v2.spaansspreken.nl

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm install --no-audit --no-fund
npm run build

php artisan migrate --force
php artisan optimize
```

Voer bij de eerste deployment vóór `php artisan migrate --force` eenmaal uit:

```bash
php artisan key:generate
```

## Rechten en gezondheidscontrole

Laravel moet kunnen schrijven naar `storage` en `bootstrap/cache`. Ploi stelt deze rechten normaal automatisch in voor de sitegebruiker. Controleer na deployment:

```bash
php artisan about
php artisan test
```

Open vervolgens `https://v2.spaansspreken.nl/up`. Een succesvolle respons bevestigt dat Laravel is opgestart. De startpagina op `/` bevestigt daarnaast dat de Vite-assets zijn gebouwd.

## Lockbestanden

`composer.lock` en `package-lock.json` staan in GitHub. Laat Ploi deze bestanden ongewijzigd gebruiken, zodat iedere deployment exact de beoordeelde dependencyversies installeert.
