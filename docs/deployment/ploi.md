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
OPENAI_FEEDBACK_MODEL=gpt-4o-mini
OPENAI_FEEDBACK_TIMEOUT=15
FEEDBACK_ASSESSOR_VERSION=turn-rubric-v1
FEEDBACK_FORMATTER_VERSION=layered-feedback-v1
```

Commit `.env` nooit en plaats `OPENAI_API_KEY` uitsluitend in de Ploi-omgeving. Genereer bij de eerste installatie de applicatiesleutel met `php artisan key:generate`.

## Deploymentscript

Gebruik in Ploi, nadat de repository naar de gewenste branch is uitgecheckt, het versiegebonden script:

```bash
cd /home/ploi/v2.spaansspreken.nl
bash scripts/deploy-production.sh /home/ploi/v2.spaansspreken.nl
```

Het script gebruikt `composer.lock` en `package-lock.json`, voert `npm ci` uit, bouwt Vite en controleert daarna of de spelershome, Madrid-voorbereiding en dialoogmotor werkelijk in de gegenereerde bundels staan. Bij een ontbrekende of verouderde bundel stopt de deploy vóór de Laravel-caches opnieuw worden opgebouwd.

Vanaf fase 2E maakt `php artisan migrate --force` ook de accountvoortgangstabellen aan. Maak deze tabellen niet handmatig in MySQL; de migratie bevat zowel de foreign keys als de terugrolvolgorde. De deploy kan zonder verlies opnieuw worden uitgevoerd wanneer de migratie al is toegepast.

Vanaf fase 3B4 worden privé-redactiemedia standaard onder `storage/app/private/content-media` opgeslagen. Neem deze map samen met de database op in de Ploi-back-up en controleer dat de sitegebruiker er kan schrijven. Gebruik alleen een andere `CONTENT_STUDIO_MEDIA_DISK` nadat die Laravel-disk duurzaam en privé is ingericht.

Fase 3B5 levert de drie gouden-route-assets mee in de code en installeert ze via `game:install-demo-content` op diezelfde privé-disk. Voer het commando na deployment bewust als bestaande beheerder uit, controleer de nieuwe conceptrevisies in de preview en publiceer ze daarna via de normale releaseflow. De installatie publiceert niets automatisch.

Wanneer de droge controle uitsluitend oude, onvolledige Madrid- of La Espiga-placeholders zonder `scene`, media en releasekoppeling meldt, kan een beheerder ze gecontroleerd vervangen:

```bash
php artisan game:install-demo-content --actor=beheerder@example.com --dry-run --replace-existing
php artisan game:install-demo-content --actor=beheerder@example.com --replace-existing --confirm=OVERSCHRIJVEN
```

Gebruik deze optie nooit als vervanging voor inhoudelijke vergelijking. De commandogrens weigert zelf alle werkelijk speelbare, gepubliceerde, gearchiveerde of releasegebonden content en bewaart de oude placeholder als onveranderlijke revisie.

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

Controleer na iedere frontenddeploy ook de live bundels:

```bash
node scripts/smoke-live-frontend.mjs https://v2.spaansspreken.nl
```

Deze rooktest haalt de CSS en JavaScript op waarnaar de live HTML verwijst. Daardoor detecteert hij ook een geslaagde Git-pull met achtergebleven `public/build`-bestanden.

## Lockbestanden

`composer.lock` en `package-lock.json` staan in GitHub. Laat Ploi deze bestanden ongewijzigd gebruiken, zodat iedere deployment exact de beoordeelde dependencyversies installeert.
