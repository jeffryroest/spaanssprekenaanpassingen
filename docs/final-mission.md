# Dag 7 · El reto final

De slotmissie brengt de speler terug naar Lucía in La Espiga. Het gesprek gebruikt opnieuw de goedgekeurde scène- en karaktermedia van dag 1, maar heeft een eigen privé scenario `madrid-final-lucia` met het contract `final_text_dialogue`.

## Speelroute

De speler voltooit exact vijf actieve beurten: terugkeren en begroeten, terugblikken op de week, een niveauspecifieke wijziging oplossen, een volgend Spaans doel kiezen en de bestelling afronden. A0 kiest een drankje, A1 accepteert een alternatief en A2 motiveert een voorkeur. Publicatie verloopt altijd via review en een expliciete productierelease; het demopakket maakt alleen een concept.

## Minimaal NPC-geheugen

De game leidt herkenning uitsluitend af uit rijen in `user_mission_progress` met status `completed`. Daardoor kan Lucía een terugkerende speler herkennen en kan de interface tonen welke vaste personages al zijn ontmoet. Het systeem bewaart en hergebruikt geen vrije antwoorden, audio, transcripties, feedback of medische en reisdetails als geheugen.

De auteur beheert de terugkeer- en eerste-bezoekbegroeting, de vaste bronmissie, de vijf terugbliklabels en de privacytoelichting in de `memory`-sectie van het contentcontract. De runtime-identiteit blijft begrensd tot Lucía en de eerste bakkerijmissie.

## Beloningen en vervolg

De finale geeft de stempel `stamp.madrid_week_complete`, de Madridkaart en een ontgrendeling voor de volgende bestemming. Opnieuw spelen is idempotent: een bestaand voltooiingsverzoek geeft geen dubbele valuta of beloningen.
