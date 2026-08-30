# Gelaagde beurtfeedback

Fase 2D voegt persoonlijke, rubricgestuurde feedback toe na een betekenisvol antwoord. De dialoog blijft deterministisch: de bestaande Content Studio-regels beslissen of de intentie geldig is, welke reactie de NPC geeft, wat de volgende stap is en welke voortgang ontstaat. De feedbackservice mag die uitkomst niet wijzigen.

## Ervaringsvolgorde

1. De speler spreekt, typt of kiest een voorbeeldzin.
2. De browser bepaalt met de gepubliceerde conversatieregels of de betekenis past.
3. Bij succes wordt de voortgang lokaal klaargezet en krijgt de feedback-endpoint alleen het gecontroleerde antwoord, de stap-id en bronmetadata.
4. De server haalt de exacte productiegepubliceerde stapcontext zelf op.
5. De beoordelaar levert vijf scores van 0–4, een sterk punt en één verbeterfocus.
6. De formatteringslaag voegt de vaste uitspraakstatus en gewogen totaalscore toe.
7. De speler kan doorgaan of de volledige succesvolle beurt veilig terugdraaien en opnieuw proberen.

Een fout, timeout, ontbrekende sleutel of ongeldig modelantwoord toont de bestaande feedback uit de Content Studio. De beurt blijft speelbaar en de speler kan altijd doorgaan.

## Vertrouwensgrenzen

- De client mag alleen `step_id`, antwoord, niveau, invoerbron en transcriptmetadata insturen.
- De server vertrouwt geen taakomschrijving of rubric van de client, maar leest de onveranderlijke productie-release van de routespecifieke scenarioslug.
- De OpenAI-respons gebruikt een strikt JSON-schema en wordt daarna nogmaals server-side gevalideerd.
- Modeloutput bepaalt nooit gespreksrouting, missievoortgang, spreekdoel, XP of beloningen.
- Antwoorden en feedback worden niet server-side opgeslagen en niet naar productanalytics gestuurd.
- De browser plaatst alleen versies en de afgeleide totaalscore in de tijdelijke sessiehistorie; de volledige modeltekst is niet nodig om de missie te hervatten. Bij het gevoelige gezondheidsrollenspel worden daarnaast antwoord- en NPC-tekst uit de opgeslagen sessiekopie verwijderd.
- De beoordelaar beoordeelt alleen taal en communicatieve taakuitvoering. Medische beoordeling, diagnose of advies is expliciet verboden.

## Rubric en uitspraakgrens

Taakuitvoering en begrijpelijkheid wegen elk 25%. Woordkeuze, grammatica en gespreksstrategie wegen elk 12,5%. Omdat de feedback-endpoint geen audio ontvangt, wordt de totaalscore over deze vijf beoordeelde dimensies genormaliseerd.

Uitspraak staat verplicht als `not_assessed` met score `null` in het publieke contract. WebM/Opus blijft het opnameformaat en fase 2D introduceert geen ffmpeg-omzetting. Een echte uitspraakbeoordeling volgt alleen na een apart product- en privacybesluit over geldige audio-evidence.

De compacte laag toont altijd eerst één sterk punt en daarna precies één concrete focus. De uitklapbare laag toont de volledige rubric. De herkansknop zet de lokale toestand terug naar vóór de succesvolle beurt, zodat tellers en historie niet dubbel oplopen.

## Endpoint

`POST /spelen/madrid/la-panaderia/feedback`

Voorbeeldinvoer:

```json
{
  "step_id": "turn.finish_order",
  "answer": "Quiero una pan y una napolitana, por favor.",
  "level": "A1",
  "source": "speech",
  "transcript_confidence_status": "ok",
  "transcript_corrected": false
}
```

Het responscontract staat in `docs/contracts/turn-feedback-v1.schema.json`. De endpoint is begrensd op twintig verzoeken per minuut per IP-adres en browsersessie.

## Omgevingsvariabelen

```dotenv
OPENAI_API_KEY=...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_FEEDBACK_MODEL=gpt-4o-mini
OPENAI_CONNECT_TIMEOUT=5
OPENAI_FEEDBACK_TIMEOUT=15
FEEDBACK_ASSESSOR_VERSION=turn-rubric-v1
FEEDBACK_FORMATTER_VERSION=layered-feedback-v1
```

De sleutel blijft uitsluitend een Ploi-servervariabele. Na wijziging van variabelen moet de configuratiecache opnieuw worden opgebouwd.

## Verificatie

```bash
npm run validate:feedback
php artisan test --filter=TurnFeedback
php artisan test --filter=PublishedConversationTurnResolver
```

## Externe API-bronnen

- [OpenAI Chat Completions API](https://developers.openai.com/api/reference/resources/chat/subresources/completions/methods/create)
- [OpenAI GPT-4o mini](https://developers.openai.com/api/docs/models/gpt-4o-mini)
- [OpenAI GPT Audio](https://developers.openai.com/api/docs/models/gpt-audio)
