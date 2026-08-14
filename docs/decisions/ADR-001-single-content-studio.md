# ADR-001 — Eén zelfstandige Content Studio

- Status: geaccepteerd
- Datum: 2026-08-14

## Besluit

Spaansspreken.nl krijgt een eigen Content Studio en is de enige bron van waarheid voor actieve spel- en leercontent.

Espaans.nl en Spaanswoordvandedag.nl zijn geen technische runtime-afhankelijkheden. De eerste versie bevat geen koppeling met hun beheersystemen of databases.

## Externe import

Woordenlijsten uit andere systemen mogen optioneel worden aangeleverd via een versieerbare import. Geïmporteerde records:

1. komen in staging;
2. bewaren bron en importmoment;
3. worden gevalideerd en ontdubbeld;
4. krijgen standaard de status `draft` of `needs_review`;
5. worden pas na menselijke beoordeling onderdeel van een les, gesprek of missie.

## Gevolgen

- Het platform blijft zelfstandig beschikbaar wanneer andere websites wijzigen.
- Contentredactie krijgt één consistente workflow.
- Import is eenvoudiger te beveiligen en te testen.
- Er is een expliciete redactiestap nodig voordat inspiratiecontent speelbaar wordt.
