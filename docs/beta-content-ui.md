# Fase 4B3 · proefweekcontent en uniforme spelersinterface

Deze ingevoegde fase sluit de twee zichtbare bètablokkades vóór Content Studio 2.0: de proefweek moet als volledig pakket aantoonbaar live kunnen staan en iedere spelerspagina moet dezelfde productidentiteit en hoofdnavigatie gebruiken.

## Complete spelersroute

De Content Studio controleert voortaan acht onderdelen: de openbare Madrid-wereld en dag 1 tot en met 7. Dag 4 is bewust dynamisch en wordt uit werkelijk voltooide, nog gepubliceerde bronmissies samengesteld. De overige dagen zijn versiegebonden Content Studio-records.

Iedere vaste gespreksdag vereist een `scene_background` en `npc_expression_sheet`. De set bevat elf unieke WebP-assets met alt-tekst, eigendomsstatus en herleidbare maker-/bronmetadata. De finale hergebruikt La Espiga en Lucía; beeld is ondersteunend en de bestaande HTML/CSS-scènes blijven als fouttolerante fallback beschikbaar.

## Publicatie

De publicatiegrens blijft menselijk en traceerbaar:

1. voer `game:publish-trial-week-content` met `--dry-run` uit;
2. los ieder inhoudelijk of media-conflict in de Content Studio op;
3. gebruik een beheerder als uitgever en een ander account met goedkeuringsrecht als reviewer;
4. bevestig de productierelease expliciet met `--confirm=PUBLICEREN`;
5. controleer op Bètastatus dat Madrid en alle zeven dagen gereed zijn.

Het commando hergebruikt de bestaande indien-, review-, release- en preflightacties. Het omzeilt geen rechten en overschrijft geen aangepaste content.

Exacte, eerder gepubliceerde pakketcontent zonder de nieuwe media wordt atomair naar een nieuwe revisie gebracht: voorbereiding, onafhankelijke review en productierelease slagen samen of worden samen teruggedraaid. Afwijkende of gedeeltelijk aangepaste content blijft ter bescherming een handmatige blokkade.

## Spelersinterface

De startpagina, Madrid, alle zeven missiedagen, Mijn proefweek, Voortgang, Account, betaalstatus en privacy gebruiken dezelfde Blade-componenten voor:

- het Spaansspreken.nl-woordmerk;
- de vaste volgorde Madrid, Mijn proefweek, Voortgang en Account;
- contextuele toegang tot Content Studio en Uitloggen;
- een toetsenbordbedienbaar mobiel menu;
- een afzonderlijke contextbalk voor missiebediening en terugnavigatie.

De Content Studio houdt bewust zijn eigen beheerschil. Zo blijft de spelerskant een warme interactieve Madrid-wereld en wordt redactioneel beheer niet met de game-interface vermengd.

## Acceptatiecriteria

- Madrid en zeven proefweekdagen zijn afzonderlijk zichtbaar in de Bètastatus.
- Dag 2, 3 en 5 hebben eigen beheerde scène- en personagemedia.
- Een droge pakketcontrole schrijft geen content, media, review of release.
- Een echte pakketvrijgave vereist twee verschillende bevoegde accounts en `PUBLICEREN`.
- Alle spelerspagina's tonen hetzelfde logo en dezelfde primaire menustructuur op desktop en mobiel.
- Ontbrekende runtime-media laat de missie met een toegankelijke fallback speelbaar.
