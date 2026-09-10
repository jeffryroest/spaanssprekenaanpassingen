# Fase 4B1 — spelersentree en accountbasis

## Doel

Deze fase verbindt de publieke marketingroute met de interactieve game en geeft spelers het minimale veilige accountbeheer dat vóór een gesloten bèta nodig is. De game-home op `v2.spaansspreken.nl` blijft het beginpunt na inloggen; Content Studio-accounts mogen vanuit dezelfde authenticatie-ingang direct naar hun werkruimte.

## Routes

| Route | Doel | Toegang |
|---|---|---|
| `GET/POST /inloggen` | Inloggen | gast |
| `GET/POST /aanmelden` | Gratis spelersaccount maken | gast, beperkt per IP |
| `GET/POST /wachtwoord-vergeten` | Generieke herstelmail aanvragen | gast, beperkt per IP |
| `GET /wachtwoord-herstellen/{token}` | Herstelformulier | gast |
| `POST /wachtwoord-herstellen` | Nieuw wachtwoord vastleggen | gast, beperkt per IP |
| `GET /account` | Account- en toegangsstatus | ingelogd |
| `PUT /account/profiel` | Naam/e-mailadres wijzigen | ingelogd |
| `PUT /account/wachtwoord` | Wachtwoord wijzigen | ingelogd, beperkt per IP |
| `GET/POST /content-studio/accounts...` | Spelers zoeken en supportstatus zien | alleen beheerder |

De oude paden `/login` en `/register` verwijzen permanent naar de Nederlandse routes. Een e-mailwijziging vereist het huidige wachtwoord. Alle account- en beheerresponses zijn privé en worden niet geïndexeerd.

## Privacy- en supportgrens

Het supportoverzicht toont naam, e-mailadres, accounttype, laatste abonnementsstatus, plan, registratiemoment en het aantal structurele missiepogingen. Het toont geen antwoordtekst, audio, transcript, AI-feedback of providergeheimen. Zoektermen worden via POST verstuurd en komen daardoor niet in de URL of gewone browsergeschiedenis terecht.

Accountverwijdering en rolmutaties zijn bewust geen onderdeel van 4B1. Verwijdering wacht op het formele retentiebeleid voor betaal- en factuurgegevens. Rolwijzigingen moeten zowel toekenning als intrekking veilig ondersteunen en iedere mutatie via `content_role_audits` vastleggen.

## Acceptatiecriteria

- een nieuwe speler kan zichzelf aanmelden, is daarna ingelogd en landt op de game-home;
- een bestaande speler landt na inloggen op de game-home en kan voortgang of account openen;
- een Content Studio-account blijft direct naar de Content Studio gaan;
- een speler kan een herstelmail aanvragen zonder dat het antwoord verraadt of het account bestaat;
- een geldig hersteltoken kan het wachtwoord wijzigen;
- naam kan worden gewijzigd en een e-mailwijziging vereist het huidige wachtwoord;
- een wachtwoordwijziging vereist het huidige wachtwoord en minimaal twaalf tekens met letters en cijfers;
- alleen beheerders kunnen andere accounts zoeken en zien;
- de spelersschermen volgen ADR-002 en blijven los van het Content Studio-designsystem;
- de volledige Laravel-, structurele, frontend- en kwaliteitsvalidatie slaagt.
