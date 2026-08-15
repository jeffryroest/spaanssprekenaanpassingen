# Test- en kwaliteitsstraat

Fase 1G maakt de kwaliteitsgrens van het skelet reproduceerbaar in GitHub Actions. De workflow staat in `.github/workflows/quality.yml` en draait op iedere pull request naar `main`, iedere push naar `main` en handmatig via `workflow_dispatch`.

## Verplichte signalen

| Check | Inhoud | Runtime |
|---|---|---|
| `PHP quality` | Composer-validatie, Laravel Pint en dependency-audit | PHP 8.4 |
| `Frontend quality` | npm-lockinstallatie, projectcontracten, Vite-productiebuild en dependency-audit | Node.js 24 |
| `Laravel tests (MySQL)` | alle unit- en featuretests na een echte migratie | PHP 8.4 + MySQL 8.4 |

De jobs draaien parallel, hebben een expliciete timeout en gebruiken alleen `contents: read`. Checkoutcredentials worden na het ophalen niet bewaard. Een nieuwere commit op dezelfde pull request annuleert de verouderde workflowrun.

Er zijn bewust geen padfilters. Daardoor worden de drie checks ook bij documentatie- of configuratiewijzigingen gerapporteerd en kunnen ze later zonder blijvende `Pending`-status als branch protection worden verplicht.

## Lokale equivalenten

Voer vóór publicatie minimaal uit:

```bash
composer validate --strict --no-check-publish
composer install --prefer-dist --no-interaction
vendor/bin/pint --test
composer audit --locked --no-interaction

npm ci --ignore-scripts
npm run validate
npm run build
npm audit --audit-level=high

php artisan migrate --force --no-interaction
php artisan test
```

Gebruik lokaal of op staging nooit productiegegevens voor de testdatabase.

## Dependency-locks

`package-lock.json` legt de frontenddependencyboom vast en maakt `npm ci` mogelijk. De eerste CI-run exporteert daarnaast het door Composer opgeloste `composer.lock` als kortlevend artifact. Dat bestand wordt in dezelfde fase aan de repository toegevoegd, waarna Composer-installaties eveneens exact reproduceerbaar zijn.

## Branch protection

Nadat deze workflow eenmaal succesvol op `main` heeft gedraaid, stel in GitHub voor `main` de volgende vereiste checks in:

- `PHP quality`;
- `Frontend quality`;
- `Laravel tests (MySQL)`.

Beperk daarnaast rechtstreeks pushen naar `main` en vereis minimaal één goedkeuring. Dit is een repository-instelling en wordt bewust niet door de workflow zelf gewijzigd.

## Foutafhandeling

- Open de mislukte job en begin bij de eerste rode stap.
- Los formattering lokaal op met `vendor/bin/pint`; commit alleen de bedoelde wijzigingen.
- Gebruik `npm run validate` voor contract- en structuurfouten.
- Een MySQL-fout vóór de teststap wijst meestal op migratie-, connectie- of servicegezondheid.
- Schakel audits niet stil uit; documenteer en verhelp of accepteer een risico expliciet.

## Acceptatiecriteria

- de workflow draait voor iedere PR naar en iedere push op `main`;
- iedere job gebruikt de vastgelegde PHP-, Node- en MySQL-hoofdversie;
- PHP-formattering, frontendbuild, contractschema's en dependency-audits blokkeren bij fouten;
- alle Laravel-tests draaien tegen een lege, gemigreerde MySQL-testdatabase;
- de workflow heeft geen schrijfpermissies en gebruikt geen repositorysecrets;
- frontend- en PHP-dependencies worden met lockbestanden reproduceerbaar gemaakt;
- de drie checks kunnen na een eerste groene run als branch protection worden verplicht.
