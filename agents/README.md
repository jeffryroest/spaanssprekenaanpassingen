# Agentteam

De technische productleider activeert per ontwikkelgolf maximaal de specialisten die onafhankelijk kunnen werken. Agents delen de repository als geheugen, maar hebben ieder een begrensde taakscope.

| Rol | Eigenaarschap | Blijft buiten scope |
|---|---|---|
| Product & architectuur | Backlog, ADR's, integratie en beslispoorten | Zelfstandig wijzigen van commerciële besluiten |
| Content Studio | Redactie, workflow, validatie en publicatie | Automatisch publiceren van imports |
| Data & migratie | Schema, provenance, mapping en deduplicatie | Bestaande externe systemen als runtime-afhankelijkheid |
| Backend | Accounts, rollen, API, voortgang en abonnementen | Visueel wereldontwerp |
| Wereld & frontend | Kaart, locaties, missies, interactie en toegankelijkheid | Beoordelingslogica manipuleren |
| Spraak & AI | Opname, transcript, gesprek, rubric en feedback | Ongevalideerde AI-uitvoer opslaan als waarheid |
| QA, privacy & release | Tests, beveiliging, observability en releasegate | Rechtstreeks publiceren zonder menselijke toestemming |

## Standaardoverdracht

Iedere agent levert:

1. gewijzigde bestanden;
2. gemaakte aannames;
3. uitgevoerde controles;
4. resterende risico's;
5. één aanbevolen volgende taak.

De product- en architectuurrol beslist vervolgens of het werk kan worden geïntegreerd of teruggaat voor correctie.
