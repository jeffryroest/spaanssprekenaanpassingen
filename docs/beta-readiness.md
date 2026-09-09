# Fase 4A — bèta-gereedheid

## Doel

Fase 4A maakt de stap van een technisch complete proefweek naar een beheersbare gesloten bèta. De beheerder krijgt één afgeschermd overzicht voor geaggregeerde productvoortgang, productieconfiguratie en operationele signalen.

## Geaggregeerde cohortmeting

`/content-studio/beta` toont voor nieuwe spelers uit de afgelopen 7, 30 of 90 dagen:

- aangemaakte spelersaccounts;
- gestarte proefweken;
- spelers met minimaal één voltooide missie;
- spelers met minimaal één opgeslagen spreekbeurt;
- spelers die de finale voltooiden;
- spelers met een actuele betaalde bestelling.

Content Studio-accounts worden uitgesloten. De pagina leest alleen bestaande structurele mijlpalen en toont geen namen, e-mailadressen, providerreferenties, antwoorden, audio of transcripties. Er worden geen trackingcookies of nieuwe analyticsrecords toegevoegd. De percentages zijn steeds het aandeel van het instroomcohort, niet van bezoekers of anonieme sessies.

## Operationele gereedheid

De beheerder ziet uitsluitend veilige ja/nee-controles voor:

- productieomgeving, uitgeschakelde debugmodus en HTTPS;
- gepubliceerde runtimecontent;
- transactiemail en vaste afzender;
- geactiveerde Mollie-verificatie en checkout;
- complete factuurafzender;
- een scheduler-heartbeat jonger dan vijf minuten;
- betaalmails die niet langer dan dertig minuten wachten of na vijf pogingen zijn gestopt.

Configuratiegeheimen en bedrijfsgegevens worden nooit weergegeven. De scheduler legt iedere minuut alleen een tijdstip in de bestaande cache vast. De bestaande Ploi-cron met `php artisan schedule:run` is hiervoor voldoende.

## Browserbeveiliging

Alle responses krijgen een kleine, compatibele basisset headers:

- framing en object-embeds zijn geblokkeerd;
- MIME-sniffing is uitgeschakeld;
- referrerinformatie wordt beperkt;
- camera en locatie zijn geblokkeerd;
- microfoon blijft uitsluitend voor dezelfde origin toegestaan;
- HTTPS-responses krijgen HSTS.

De CSP beperkt in deze stap bewust alleen `base-uri`, `frame-ancestors` en `object-src`. Een volledige nonce-gebaseerde script- en style-CSP volgt pas nadat alle bestaande inline frontendcode is geïnventariseerd en aangepast.

## Open productiepoorten

- Voer met Mollie en Postmark één volledige gecontroleerde aankoop, factuurdownload en opzegging uit.
- Bevestig met de administrateur de bewaartermijn en verwijderprocedure voor bestel- en factuurgegevens.
- Controleer de gouden route handmatig op recente mobiele Safari, Chrome, Firefox en Edge, inclusief toetsenbord en 200% tekstzoom.
- Start daarna met een kleine gesloten groep en beoordeel supportincidenten en het cohortoverzicht voordat bredere werving begint.
