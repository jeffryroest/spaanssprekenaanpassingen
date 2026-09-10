# Fase 4B2 — accountbeheer, support en verwijdering

## Doel

Deze fase geeft alleen beheerders een afgeschermd accountdetail met operationele status, geauditeerde rolmutaties en minimale supportnotities. Spelers kunnen zelf een verwijderverzoek indienen. Vrije leerinhoud, audio, transcripties, AI-feedback en providergeheimen blijven buiten het supportdossier.

## Rollen en support

- Alleen de rol `beheerder` kan accountdetails openen of wijzigen.
- Iedere roltoekenning en -intrekking loopt via `AssignContentRole` en wordt in `content_role_audits` vastgelegd.
- Een beheerder kan de eigen rol niet wijzigen; de laatste beheerder kan niet worden verwijderd.
- Supportnotities bevatten een categorie, een korte operationele samenvatting, auteur, tijdstip en optionele opvolgdatum.
- De interface waarschuwt expliciet geen antwoorden, transcripties, audio of AI-feedback in notities op te nemen.

## Verwijderproces

1. De speler dient in het eigen account een verzoek in met het huidige wachtwoord en een expliciete bevestiging.
2. Een beheerder controleert het account en bevestigt de verwerking opnieuw met het accountadres en het eigen wachtwoord.
3. Een nog actief, proef- of achterstallig abonnement blokkeert verwerking. Het abonnement moet eerst worden afgerond om nieuwe afschrijvingen te voorkomen.
4. Bij verwerking worden profielgegevens gepseudonimiseerd en sessies, wachtwoordresets, supportnotities en alle spelvoortgang verwijderd.
5. Bestellingen, abonnementen, provider-events en facturen blijven afzonderlijk behouden wanneer zij bij de fiscale administratie horen. Het verzoek registreert de berekende einddatum van deze bewaring.

De bewaartermijn is configureerbaar via `FISCAL_RETENTION_YEARS` en staat standaard op zeven jaar. De Nederlandse Belastingdienst noemt zeven jaar voor basisgegevens en facturen: [bewaartermijnen administratie](https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/zakelijk/ondernemen/administratie/hoelang-moet-u-gegevens-bewaren) en [facturen bewaren](https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/zakelijk/btw/administratie_bijhouden/facturen_maken/uw_facturen_bewaren).

Een automatische fysieke purge van de fiscale administratie is bewust niet opgenomen. Die operatie vereist vóór ingebruikname een afzonderlijke juridische en boekhoudkundige controle op de concrete administratie, eventuele OSS-situatie en startdatum van de termijn.

## Acceptatiecriteria

- alleen beheerders kunnen accountdetails, notities, rolmutaties en verwijderverzoeken beheren;
- roltoekenning én rolintrekking zijn geaudit;
- eigen rolmutatie en verwijdering van de laatste beheerder zijn geblokkeerd;
- supportnotities hebben vaste categorieën en auteurschap;
- een verwijderverzoek vereist het huidige spelerswachtwoord en kan vóór verwerking worden ingetrokken;
- definitieve verwerking vereist het beheerderswachtwoord plus exacte e-mailbevestiging;
- een nog actief abonnement blokkeert accountwissing;
- spel- en accountgegevens verdwijnen, terwijl vereiste betaalgegevens blijven bestaan met een zichtbare einddatum;
- alle account- en supportpagina's gebruiken private caching en zijn niet indexeerbaar.
