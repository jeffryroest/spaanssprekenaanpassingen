import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Http/Controllers/Game/SpeechTranscriptionController.php',
  'app/Http/Requests/Game/TranscribeSpeechRequest.php',
  'app/Speech/OpenAiTranscriber.php',
  'config/transcription.php',
  'docs/contracts/speech-transcription-v1.schema.json',
  'docs/webm-transcription.md',
  'resources/js/game/panaderia-recorder.js',
  'resources/views/game/panaderia.blade.php',
  'resources/views/privacy.blade.php',
  'tests/Feature/Speech/SpeechTranscriptionTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [controller, request, transcriber, config, schemaSource, docs, recorder, view, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[2]), read(paths[3]), read(paths[4]), read(paths[5]), read(paths[6]), read(paths[7]), read('routes/web.php'),
]);
const schema = JSON.parse(schemaSource);

assert(schema.properties?.meta?.properties?.audio_persisted?.const === false, 'Het contract moet bevestigen dat audio niet wordt bewaard');
assert(schema.properties?.meta?.properties?.maximum_duration_seconds?.const === 12, 'Het contract moet de 12-secondenlimiet vastleggen');
assert(routes.includes("Route::post('/spelen/madrid/la-panaderia/transcriptie'"), 'De transcriptieroute ontbreekt');
assert(routes.includes('throttle:speech-transcriptions'), 'De transcriptieroute moet rate limiting gebruiken');
assert(request.includes('max:2048') && request.includes('12.5') && request.includes('\\x1A\\x45\\xDF\\xA3'), 'Bestandsgrootte, duur of WebM-signatuur wordt niet begrensd');
assert(transcriber.includes("'/audio/transcriptions'") && transcriber.includes("'language' => 'es'"), 'De adapter moet de Spaanse transcriptie-endpoint gebruiken');
assert(transcriber.includes("'include[]' => 'logprobs'"), 'De adapter moet transcript-confidence kunnen bepalen');
assert(config.includes('gpt-4o-mini-transcribe'), 'Het gedocumenteerde standaardmodel ontbreekt');
assert(controller.includes("'audio_persisted' => false"), 'De respons moet expliciet melden dat audio niet wordt bewaard');
assert(view.includes('data-speech-recorder') && view.includes('data-recording-playback'), 'Recorder of lokale playback ontbreekt');
assert(recorder.includes('MediaRecorder') && recorder.includes('audio/webm;codecs=opus'), 'WebM/Opus MediaRecorder ontbreekt');
assert(recorder.includes('getUserMedia') && recorder.includes('maximumSeconds'), 'Expliciete microfoontoestemming of tijdslimiet ontbreekt');
assert(recorder.includes("confidenceStatus === 'low'") && !recorder.includes('pronunciation_score'), 'Lage confidence mag geen uitspraakscore veroorzaken');
assert(!`${controller}${request}${transcriber}${recorder}`.includes('ffmpeg'), 'Fase 2C mag geen ffmpeg-conversie introduceren');

console.log('WebM-transcriptie geldig: expliciete toestemming, 12 seconden, lokale playback, veilige upload, Spaanse transcriptie en tekstfallback.');
