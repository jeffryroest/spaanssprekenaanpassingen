# Content Studio-designsystem

Fase 1C.1 geeft de volledige bestaande Content Studio één samenhangende, responsieve beheerstijl. De richting is geïnspireerd op de informatiearchitectuur van TailAdmin, maar is zelfstandig uitgevoerd in de bestaande Laravel 13- en Tailwind CSS 4-stack.

## Ontwerpbesluit

- de Content Studio gebruikt een lichte, rustige werkruimte voor inhoudelijk en langdurig redactiewerk;
- een donkere zijbalk scheidt globale navigatie van de actuele taak;
- Spaansspreken-oranje is de primaire actie- en merkaccentkleur;
- semantische kleuren zijn gereserveerd voor succes, review, waarschuwing en risico;
- de bestaande Blade-views, routes, policies en server-side validatie blijven de bron van waarheid;
- er wordt geen volledige externe adminstarter of extra JavaScript-framework geïntroduceerd.

## Gedeelde bouwstenen

De gedeelde CSS-componenten in `resources/css/app.css` verzorgen panelen, knoppen, velden, meldingen, paginatitels en navigatiestatussen. Blade-componenten leveren herbruikbare pictogrammen en workflowbadges. Daardoor hoeven nieuwe modules niet zelf kleur- of focusregels te bedenken.

De applicatieschil bevat:

- een vaste desktopzijbalk en toetsenbordbedienbare mobiele drawer;
- een compacte bovenbalk met kruimelpad en gebruikersmenu;
- een zichtbare skiplink naar de hoofdinhoud;
- duidelijk gemarkeerde actieve en nog niet beschikbare onderdelen;
- een inhoudsbreedte die zowel formulieren als brede catalogustabellen ondersteunt.

## Toegankelijkheid en mobiel

- alle interactieve bediening heeft een zichtbare focusstatus;
- de mobiele navigatie kan met `Escape` worden gesloten;
- overlays, menu's en navigatie hebben toegankelijke labels en actuele `aria-expanded`-waarden;
- kleur is nooit de enige statusindicator: badges bevatten ook tekst;
- de interface respecteert `prefers-reduced-motion`;
- formulieren koppelen foutmeldingen met `aria-invalid` en `aria-describedby` aan het betreffende veld;
- acties hebben op mobiel minimaal ongeveer 44 pixels aanraakhoogte.

## Afbakening

Deze fase wijzigt geen database, contentworkflow, autorisatie of publieke routes. Donkere modus wordt bewust uitgesteld totdat de nieuwe functionele schermen stabiel zijn; daarmee blijft het designsystem kleiner en wordt contrast niet dubbel onderhouden.

## Acceptatiecriteria

- login, 403, dashboard, catalogus, create, edit en detail gebruiken dezelfde visuele taal;
- de bestaande autorisatie- en CRUD-tests blijven slagen;
- de Vite-productiebundel bouwt zonder extra frontendafhankelijkheden;
- de navigatie blijft bruikbaar op mobiel, desktop en met toetsenbord;
- nieuwe workflowmodules kunnen de gedeelde componentklassen en Blade-componenten hergebruiken.
