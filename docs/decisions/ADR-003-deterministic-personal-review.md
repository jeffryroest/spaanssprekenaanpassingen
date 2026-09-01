# ADR-003 — Deterministische persoonlijke herhaling zonder antwoordopslag

- Status: geaccepteerd
- Datum: 2026-09-01

## Besluit

Persoonlijke herhaling wordt samengesteld uit structurele bewijsvelden van werkelijk voltooide missieroutes en de nog geldige gepubliceerde bronrevisie. De planning gebruikt een transparant intervalmodel met vier zelfbeoordelingen (`again`, `hard`, `good`, `easy`). AI kiest geen kaarten of intervallen.

De persoonlijke spreek- of tekstpoging is vluchtig. Alleen gehashte kaart-id, bronmissie/-revisie, stap-id, invoerbron, hulpgebruik, zelfbeoordeling en volgende oefendatum worden opgeslagen.

## Redenen

- De speler krijgt nuttige continuïteit zonder vrije gesprekstekst tot een persoonlijk profiel te maken.
- Redactie blijft eigenaar van alle getoonde prompts, NPC-regels, hints en voorbeelden.
- De selectie en volgende datum zijn uitlegbaar en volledig regressietestbaar.
- Een ingetrokken of ongeldig gepubliceerde bron levert geen nieuwe herhalingskaart.

## Beloningsgrens

Herhalingssessies leveren samen maximaal 20 XP per kalenderdag en maximaal eenmaal 1 Confianza wanneer een sessie minstens drie gesproken kaarten bevat. Ledgerregels blijven append-only en gebruiken een unieke idempotencykey per voltooiingssleutel en valuta.

## Gevolgen

- `user_practice_items` is een vervangbare projectie en niet de bron van leercontent.
- `mission_attempts` bewaart een append-only structurele sessiehistorie.
- Een volgende fase kan het intervalmodel wijzigen, maar vereist dan een nieuwe contract-/beslisversie.
- Minimaal NPC-geheugen blijft een aparte fase en mag deze privacygrens niet verruimen.
