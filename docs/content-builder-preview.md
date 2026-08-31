# Speelcontentbouwer en veilige preview

Fase 3B4 maakt de bestaande versieerbare speelcontracten daadwerkelijk auteurvriendelijk. De Content Studio blijft de enige bron van waarheid en bewaart nog steeds exact hetzelfde `domain_data`-contract; de nieuwe formulieren zijn een veilige bewerkingslaag boven dat contract.

## Wat een redacteur kan invullen

Voor de Madrid-wereld:

- intro, beschrijving en spelersdoel;
- hotspots met Spaanse en Nederlandse naam, toestand, actie en kaartpositie;
- onderzoekspunten, curiosidad en optionele woordparen;
- wereldillustratie en omgevingsgeluid.

Voor gesprekken:

- NPC-identiteit, rol en houding;
- missie-id, titels, doel, startpunt en vereiste actieve beurten;
- openbare of afgeschermde proefweektoegang;
- A0-, A1- en A2-vertakkingen;
- herstelzinnen en herstelreactie;
- een onbeperkt aantal route- en gespreksstappen;
- per stap NPC-regels, opdracht, hint, voorbeelden, fallback en één of meer antwoordroutes;
- herkenningsgroepen, NPC-reactie, gelaagde feedback, nieuwe toestanden en vervolgstap;
- afronding en server-side beloningswaarden;
- de volledige fictieve rolkaart en privacywaarschuwingen van gezondheidscontent;
- scèneachtergrond, NPC-portret en optioneel omgevingsgeluid.

De geavanceerde JSON blijft zichtbaar als herstelroute en voor nauwkeurige inspectie. Een redacteur hoeft hem voor de normale workflow niet meer handmatig te schrijven.

## Diepe validatie

De server controleert dezelfde content bij opslaan, reviewaanvraag en releasepreflight. Naast het contractschema worden onder andere gecontroleerd:

- unieke hotspot-, onderzoekspunt-, stap- en optie-id's;
- kaartposities tussen 0 en 100;
- minimaal één geopende wereldlocatie;
- Spaanse én Nederlandse kernteksten;
- een bestaand missiestartpunt;
- bestaande A0/A1/A2-vertakkingsstappen;
- iedere verwijzing naar een vervolgstap;
- routecycli en minimaal één volledige route naar `@complete` per niveau;
- voorbeeldantwoord, fallback, herkenningsgroepen en gelaagde feedback per spreekbeurt;
- herstelstrategie en niet-negatieve beloningen;
- de fictieve rolkaart en onafhankelijke reviewgrens voor gezondheidscontent.

Een fout schrijft geen contentobject, revisie, mediarol of auditregel gedeeltelijk weg.

## Mediabibliotheek

Redactionele media staan los van spelersopnamen. De bibliotheek accepteert JPG, PNG, WebP, MP3, OGG, WebM en WAV tot 20 MB en bewaart:

- een UUID, objectkey, werkelijke MIME, bestandsgrootte en SHA-256-checksum;
- afbeeldingsafmetingen waar beschikbaar;
- titel, beschrijving en oorspronkelijke bestandsnaam;
- alt-tekst voor afbeeldingen of transcript voor audio;
- bron, maker, licentie, rechtenstatus en eventuele vervaldatum;
- maker en auditgebeurtenis.

Bestanden blijven standaard privé op `CONTENT_STUDIO_MEDIA_DISK=local`. Alleen ingelogde Content Studio-gebruikers krijgen de streamroute. Een revisie bewaart zowel de vaste mediarol als asset-ID en UUID. Onbekende, verlopen of alleen als inspiratie toegestane rechten en ontbrekende toegankelijkheidstekst blokkeren productiepublicatie.

Mediametadata is na upload onveranderlijk. Een correctie wordt als nieuw asset geüpload en in een nieuwe contentrevisie gekoppeld, zodat een oude goedkeuring nooit stilzwijgend naar andere rechten of alt-tekst gaat verwijzen. De checksum maakt een identiek binair bestand wel herkenbaar in audit en toekomstig deduplicatiebeheer.

Voor deployment moet `storage/app/private/content-media` duurzaam worden bewaard en in de normale serverback-up vallen. Een later object-storagebesluit kan via dezelfde diskconfiguratie worden ingevoerd zonder contractsleutels te veranderen.

## Voortgangsvrije spelpreview

Via **Spelpreview** opent de actuele revisie via een één uur geldige, ondertekende URL in een nieuw, niet-indexeerbaar venster met:

- een permanente niet-productiebanner, revisie en veiligheidsmelding;
- mobiel-, tablet- en desktopbreedte;
- wereldkaart met hotspots en onderzoekspunten;
- NPC, missie en optionele rolkaart;
- A0/A1/A2-keuze;
- voorbeeldantwoord, echte lokale herkenningsgroepen, fallback, herstelstrategie, feedback en routehistorie;
- afronding zonder XP, beloning, accountvoortgang, API-call of analytics.

De preview gebruikt uitsluitend de opgeslagen revisiesnapshot en privémediastream. Hij publiceert niets en kan nooit als verkorte release- of rechtenroute worden gebruikt.

Deze fase maakt media beheerbaar, versiegebonden en beoordeelbaar. De publieke spelersruntime gebruikt nog de bestaande statische scène-assets; het gecontroleerd uitserveren van goedgekeurde redactionele media naar de volledige Madrid → La Espiga-route hoort bij fase 3B5.

## Deployment en terugrol

Na deployment:

```bash
php artisan migrate --force
php artisan optimize
```

De migratie voegt `media_assets` en de versiegebonden koppeltabel `content_media` toe. Maak vóór een rollback een database- én storageback-up. `php artisan migrate:rollback --step=1` verwijdert de mediametadata en koppelingen, maar verwijdert bewust geen objecten uit private storage; die moeten na controle afzonderlijk worden opgeschoond.

## Acceptatiecriteria

- voorbeeldcontent kan zonder handmatig JSON-werk inhoudelijk worden bewerkt;
- onbekende contractsleutels blijven bij gestructureerde wijzigingen behouden;
- alle bestaande voorbeeldcontracten slagen voor diepe routevalidatie;
- een ontbrekende verwijzing of routecyclus blokkeert opslag;
- media zijn privé, typegecontroleerd, toegankelijk beschreven en auditbaar;
- mediarollen zijn contenttype- en revisiegebonden;
- onpubliceerbare media blokkeren releasepreflight;
- de preview is alleen voor bevoegde gebruikers, niet indexeerbaar en schrijft geen spelersdata;
- toetsenbord, 360 px, 200% zoom en verminderde beweging blijven ondersteund.
