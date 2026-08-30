# Demo-ready Content Studio

Fase 3B2.5 maakt de bestaande spelmotor aantoonbaar vulbaar en speelbaar zonder de productiepublicatiegrens te verlagen. De fase combineert drie correcties: de gezondheidsmissie staat op dezelfde `main`-lijn als restaurant, de reviewworkflow is risicogestuurd en de versiegebonden voorbeeldcontent kan idempotent als conceptpakket worden geïnstalleerd.

## Reviewbeleid

`CONTENT_STUDIO_REVIEW_MODE` ondersteunt twee waarden:

- `risk_based` is de standaard. Een beheerder of hoofdredacteur mag een gewone eigen revisie na een verplichte motivatie goedkeuren. De auditlog gebruikt daarvoor expliciet `content.review_self_approved`.
- `strict` vereist voor iedere revisie een andere beoordelaar.

Een revisie vereist ook in `risk_based` altijd een onafhankelijke beoordelaar wanneer:

- het scene-contract `health_text_dialogue` is;
- `domain_data.review.risk_tier` gelijk is aan `high`;
- `domain_data.review.requires_independent_reviewer` expliciet `true` is.

De maker van een actuele revisie kan een reviewaanvraag intrekken. De revisie keert terug naar `Concept`; de intrekking, reden en actor blijven onveranderlijk in reviewgeschiedenis en auditlog staan.

## Inhoudelijke review- en releasegrens

Een regio of gespreksscenario kan niet meer naar review zonder ingevuld en ondersteund `domain_data.scene`-contract. De releasepreflight voert dezelfde controle opnieuw uit op de exacte revisie in de release. Hierdoor kan een eerder of handmatig inconsistent record niet alleen op basis van status en revieweridentiteit worden gepubliceerd.

Die controle vervangt de toekomstige typespecifieke editors niet. Diepe relatie-, media-, licentie- en volledige routevalidatie blijven onderdeel van de volgende Content Studio-fase.

## Demopakket installeren

Provision eerst een bestaande beheerder wanneer die nog niet bestaat:

```bash
php artisan content-studio:provision-administrator beheerder@example.com
```

Controleer daarna zonder databasewijziging wat het pakket zou doen:

```bash
php artisan game:install-demo-content --actor=beheerder@example.com --dry-run
```

Installeer de ontbrekende voorbeelden als concept:

```bash
php artisan game:install-demo-content --actor=beheerder@example.com
```

Het pakket bevat:

- Madrid-wereld;
- La Espiga met Lucía;
- taxi met Diego;
- Café El Reloj met Carmen;
- Consulta La Luz met Elena.

Het commando is veilig om opnieuw uit te voeren. Exact gelijke records blijven ongemoeid. Zodra een bestaand record inhoudelijk afwijkt, stopt de volledige installatie met een conflict en wordt niets overschreven.

Als alternatief kan een bestaande beheerder via de omgeving aan de benoemde seeder worden gekoppeld:

```dotenv
CONTENT_STUDIO_DEMO_ACTOR_EMAIL=beheerder@example.com
```

```bash
php artisan db:seed --class=PlayableDemoContentSeeder
```

De standaard `DatabaseSeeder` maakt geen vast testaccount meer aan. Op productie doet de standaardseeder niets; het benoemde commando of de benoemde seeder moet bewust worden gestart.

## Publicatie

Installatie maakt uitsluitend conceptrevisies. Daarna blijven de normale stappen verplicht:

1. controleer de speeldata;
2. dien iedere revisie in;
3. keur gewone content gemotiveerd goed of laat gevoelige content onafhankelijk beoordelen;
4. voeg de exacte versies toe aan een productierelease;
5. doorloop de preflight;
6. bevestig bewust met `PUBLICEREN`.

Er wordt geen proefabonnement, testspeler, prijs of recht aangemaakt. Toegang tot taxi, restaurant en gezondheid blijft via het bestaande `trial_week`-recht lopen.

## Acceptatiecriteria

- Een droge controle schrijft geen content of auditgebeurtenis.
- Een lege database krijgt vijf coherente conceptrecords.
- Een tweede installatie maakt geen duplicaten of revisies.
- Handmatig gewijzigde, gearchiveerde of gepubliceerde afwijkende content wordt nooit overschreven.
- Gewone eigen content kan door beheerder of hoofdredacteur gemotiveerd worden goedgekeurd.
- Gezondheidscontent vereist altijd een onafhankelijke beoordelaar.
- Een maker kan een lopende review veilig intrekken.
- Lege of ongeldige speelcontent kan niet naar review of door releasepreflight.
- Installatie publiceert nooit automatisch.
