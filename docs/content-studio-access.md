# Content Studio-toegang

Deze eerste Fase 1A-implementatie levert login, logout, redactierollen, server-side autorisatie, audit van roltoewijzingen en een beschermd dashboard.

## Afbakening

- Er is geen openbare registratie.
- Spelersaccounts, docentrollen en accountbeheer vallen buiten deze wijziging.
- Nieuwe contentfuncties worden pas toegevoegd achter de bestaande permissies.
- Alleen `Beheerder` en `Hoofdredacteur` krijgen publicatierechten; publiceren zelf volgt later.

## Rollen

De rollen volgen de functionele Content Studio-specificatie:

- Beheerder
- Hoofdredacteur
- Redacteur
- Taalreviewer
- Importbeheerder
- Auditor

Iedere rolwijziging via `AssignContentRole` wordt opgeslagen in `content_role_audits`. Rechtstreekse databasewijzigingen zijn niet toegestaan.

## Eerste beheerder aanmaken

Voer na de migraties interactief uit:

```bash
php artisan content-studio:provision-administrator beheerder@example.com --name="Naam beheerder"
```

Het wachtwoord wordt verborgen opgevraagd en niet in shellgeschiedenis, code of logs opgeslagen. Voor een bestaand account wordt alleen de rol toegewezen.

## Deployment

```bash
php artisan migrate --force
php artisan optimize
php artisan test
```

De migratie kan worden teruggedraaid met `php artisan migrate:rollback --step=1`. Daarmee verdwijnen ook roltoewijzingen en het bijbehorende auditlog; maak vóór terugrollen een databaseback-up.

## Acceptatiecriteria

- Gasten worden vanaf `/content-studio` naar `/login` gestuurd.
- Ingelogde gebruikers zonder redactierol krijgen HTTP 403.
- Alle zes redactierollen kunnen het dashboard bekijken.
- Alleen Beheerder en Hoofdredacteur hebben de publicatiepermissie.
- Login is gelimiteerd op vijf mislukte pogingen per e-mailadres/IP-combinatie.
- Logout maakt de sessie ongeldig en vernieuwt het CSRF-token.
- Roltoewijzingen worden geaudit.
