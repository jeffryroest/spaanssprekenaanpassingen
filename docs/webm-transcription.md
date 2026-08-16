# WebM/Opus-opname en Spaanse transcriptie v1

Fase 2C voegt spreken toe aan de bestaande, hervatbare La Espiga-dialoog. De browser neemt uitsluitend na een expliciete klik maximaal 12 seconden WebM/Opus op. De speler kan de opname lokaal terugluisteren, opnieuw maken en daarna bewust naar transcriptie sturen.

## Spelersflow

1. `Opnemen` vraagt pas op dat moment microfoontoestemming.
2. De recorder toont status met pictogram, tekst en timer en stopt automatisch na 12 seconden.
3. De speler luistert lokaal terug; tot dit punt verlaat de opname de browser niet.
4. `Transcript maken` verstuurt de WebM-container met CSRF-bescherming.
5. Het Spaanse transcript wordt in het bestaande antwoordveld geplaatst en blijft corrigeerbaar.
6. Pas `Gebruik antwoord` laat de bestaande gespreksregie de betekenis beoordelen.

Bij geweigerde toestemming, ontbrekende WebM/Opus-ondersteuning of provideruitval blijft tekstinvoer beschikbaar zonder voortgangsverlies.

## Servergrens

`POST /spelen/madrid/la-panaderia/transcriptie` accepteert multipartvelden:

- `audio`: een `.webm`-bestand met EBML-signatuur, maximaal 2 MiB;
- `duration_seconds`: 0,2 tot en met 12,5 seconden, waarbij de browser bij 12 seconden stopt.

De webroute gebruikt de Laravel-sessie voor CSRF en een limiet van tien pogingen per minuut per IP-adres en sessie. De server streamt de tijdelijke upload rechtstreeks naar `POST /v1/audio/transcriptions`, met `language=es`, `response_format=json` en standaardmodel `gpt-4o-mini-transcribe`. WebM is een officieel ondersteund invoerformaat. Er is geen ffmpeg-conversie of server-side audiobestand.

## Confidence en beoordeling

Wanneer de provider token-logwaarschijnlijkheden teruggeeft, zet de adapter het meetkundig gemiddelde om naar een begrensde transcript-confidence. Onder de configureerbare grens van standaard `0.65` krijgt het transcript `confidence_status=low`.

Fase 2C gebruikt confidence alleen om de speler extra te laten controleren. Er wordt nog geen uitspraakscore berekend. Een lage of ontbrekende transcript-confidence kan daardoor nooit als uitspraakfout of negatieve voortgang worden verwerkt. Gelaagde rubricfeedback volgt in fase 2D als een afzonderlijke beoordelingsservice.

## Privacy en logging

- de applicatie schrijft ruwe audio niet naar database of bestandsopslag;
- het JSON-antwoord bevestigt `audio_persisted=false`;
- ruwe audio en letterlijke transcriptie gaan niet naar productanalytics;
- de opname leeft lokaal als tijdelijke object-URL en wordt bij een nieuwe beurt of het sluiten van de pagina vrijgegeven;
- een gebruikt transcript kan als spelersantwoord in `sessionStorage` staan om dezelfde tabsessie te hervatten;
- foutmeldingen loggen geen opname of transcript.

De publieke uitleg staat op `/privacybeleid#spraakopnamen`.

## Configuratie

Configureer in Ploi uitsluitend als servervariabelen:

```dotenv
TRANSCRIPTION_DRIVER=openai
TRANSCRIPTION_LOW_CONFIDENCE_THRESHOLD=0.65
OPENAI_API_KEY=<geheime-projectsleutel>
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_TRANSCRIPTION_MODEL=gpt-4o-mini-transcribe
OPENAI_CONNECT_TIMEOUT=5
OPENAI_TRANSCRIPTION_TIMEOUT=20
```

Voer na een wijziging `php artisan optimize` uit. De sleutel hoort nooit in Git, browser-JavaScript of de Content Studio.

## Acceptatiecriteria

- opname begint nooit zonder expliciete spelersactie;
- WebM/Opus stopt automatisch op 12 seconden en is lokaal terug te luisteren;
- opnieuw opnemen, uploaden, langzame toestand en fouten zijn bruikbaar en toetsenbordbedienbaar;
- het Spaanse transcript is corrigeerbaar voordat gespreksregie plaatsvindt;
- geweigerde toestemming en provideruitval behouden de tekstfallback;
- uploadtype, EBML-signatuur, grootte en opgegeven duur worden server-side begrensd;
- CSRF, rate limiting en stabiele JSON-fouten zijn actief;
- audio wordt niet door de applicatie bewaard en ffmpeg ontbreekt;
- lage confidence heeft geen uitspraakscore of negatieve voortgang;
- contract-, feature-, build- en MySQL-tests slagen.

## Externe API-bronnen

- [OpenAI create transcription API](https://developers.openai.com/api/reference/resources/audio/subresources/transcriptions/methods/create)
- [OpenAI GPT-4o mini Transcribe](https://developers.openai.com/api/docs/models/gpt-4o-mini-transcribe)
