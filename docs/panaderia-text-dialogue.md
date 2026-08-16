# Panadería La Espiga — tekstdialoog v1

Fase 2B maakt La Espiga betreedbaar via `/spelen/madrid/la-panaderia`. De speler voert vijf betekenisvolle tekstbeurten met Lucía Martín: begroeten en brood bestellen, iets zoets kiezen, een onverwachte vraag oplossen, meenemen aangeven en betalen.

## Productiecontentgrens

De runtime haalt de dialoog uitsluitend op via `GET /api/v1/conversations/la-espiga-lucia?locale=nl-NL`. Maak in de Content Studio daarom een `conversation_scenario` met slug `la-espiga-lucia` en gebruik `content/examples/panaderia-dialogue-domain-data.json` als voorbeeld voor `domain_data`.

Het voorbeeldbestand wordt nooit rechtstreeks door de spelersroute gelezen. De bakkerij toont een duidelijke lege toestand tot exact deze conversatie is beoordeeld en via een productierelease is gepubliceerd.

## Gespreksregie

- A0 krijgt de vraag `¿Una napolitana o dos?`.
- A1 krijgt te horen dat de napolitana niet beschikbaar is.
- A2 kiest tussen een gewone en volkoren barra.
- Vrije tekst wordt accent- en leestekentolerant op groepen intentietermen beoordeeld, niet op één exacte voorbeeldzin.
- Begrijpelijke lidwoord- of vervoegingsfouten blokkeren de missie niet.
- Voorbeeldzinnen vullen het invoerveld, maar de speler mag ze aanpassen.
- Feedback noemt eerst wat communicatief lukte en daarna maximaal één volgende stap.

Herstelzinnen zoals `¿Puede repetir?`, `Más despacio, por favor` en `No entiendo` houden de speler in dezelfde beurt, leveren de state `used_repair_strategy` op en kunnen de bonusbadge `Sin miedo` ontgrendelen.

## Hervatten en beloningen

De laatste voltooide beurt, het gekozen niveau, states en het zichtbare gespreksverloop worden in deze fase in `sessionStorage` opgeslagen. Herladen hervat daardoor veilig binnen dezelfde browsersessie. Accountgebonden persistentie volgt later in fase 2.

Bij voltooiing toont de client:

- 80–120 XP op basis van zelfstandigheid;
- 1–3 Confianza;
- 1 Valentía;
- de stempel `Mi primera compra`;
- de broodzak van La Espiga;
- optioneel de badge `Sin miedo`.

## Toegankelijkheid

- alle beurten werken met toetsenbord en zonder tijdslimiet;
- Spaanse zinnen hebben `lang="es"`;
- Nederlandse vertaling staat standaard uit en kan onafhankelijk worden aangezet;
- statuswijzigingen gebruiken één `aria-live`-regio;
- keuzehulp is een alternatief naast vrije tekst en niet de enige invoer;
- het gesprek gebruikt geen essentiële informatie die uitsluitend visueel of auditief beschikbaar is.

## Uitbreiding in fase 2C

- Dezelfde vijf beurten ondersteunen nu expliciete WebM/Opus-opname, lokaal terugluisteren en Spaanse transcriptie.
- Transcriptie vult de bestaande vrije invoer; de speler controleert en corrigeert die vóór de gespreksregie.
- Tekst en keuzehulp blijven beschikbaar bij geweigerde toestemming of technische uitval.
- Er wordt geen ffmpeg-conversie geïntroduceerd.
- Gelaagde rubric- en uitspraakfeedback volgt als afzonderlijke beoordelingsservice in fase 2D.
- De intentieherkenning is deterministisch en client-side; server-side versieerbare gespreksregie volgt vóór productie-analyse.
- Beloningen worden getoond en binnen de sessie bewaard, maar nog niet aan een gebruikersaccount geschreven.

## Acceptatiecriteria

- La Espiga is vanuit de Madrid-hub te openen;
- alle vijf tekstbeurten kunnen zonder hulp worden voltooid;
- A0, A1 en A2 gebruiken drie verschillende, voltooibare complicaties;
- een herstelzin telt positief en verliest geen voortgang;
- herladen hervat de laatste voltooide stap;
- vertaling, keuzehulp en vrije tekst werken onafhankelijk;
- alleen productiegepubliceerde conversiecontent wordt getoond;
- contract-, feature-, build- en MySQL-tests slagen.
