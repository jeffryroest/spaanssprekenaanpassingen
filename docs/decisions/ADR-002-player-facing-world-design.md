# ADR-002 — Gescheiden ontwerpstelsels voor spel en Content Studio

- Status: geaccepteerd
- Datum: 2026-08-16

## Besluit

De Content Studio blijft een rustige, taakgerichte beheeromgeving in de stijl van Tailwind Admin Free. De spelersfrontend krijgt een eigen visuele wereldlaag die aansluit op de productblauwdruk: rijk geïllustreerde 2D, lichte diepte, subtiele animatie en warm hedendaags Madrid.

Het spelpalet bestaat uit warm steen, terracotta, saffraan, olijfgroen en nachtblauw. Interface, tekst en interactieve hotspots blijven echte HTML. Illustraties zijn decoratief of oriënterend en bevatten nooit de enige representatie van essentiële informatie.

## Grenzen

- Publieke spelerspagina’s tonen geen fasenummers, frameworknamen of deploymentstatus.
- Content Studio blijft de enige bron van actieve spel- en leercontent.
- Startertemplates maken uitsluitend concepten; review en productiepublicatie blijven expliciete menselijke handelingen.
- Beweging respecteert `prefers-reduced-motion`.
- De kernlus blijft op 360 pixels breed, met toetsenbord, schermlezer en 200% zoom bruikbaar.
- Culturele details tonen hedendaags dagelijks Spanje zonder generieke flamenco-, stierenvecht- of sombreroclichés.

## Gevolgen

De beheeromgeving kan informatiecompact blijven zonder de spelbeleving kaal te maken. Nieuwe missies hergebruiken de gespreksmotor, maar krijgen per locatie een spelergerichte wereldscène en een korte voorbereiding/overgang.
