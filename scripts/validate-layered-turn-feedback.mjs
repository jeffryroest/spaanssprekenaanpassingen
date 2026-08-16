import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Feedback/Contracts/TurnAssessor.php',
  'app/Feedback/Contracts/TurnContextResolver.php',
  'app/Feedback/LayeredFeedbackComposer.php',
  'app/Feedback/OpenAiTurnAssessor.php',
  'app/Feedback/PublishedConversationTurnResolver.php',
  'app/Http/Controllers/Game/TurnFeedbackController.php',
  'app/Http/Requests/Game/AssessTurnRequest.php',
  'config/feedback.php',
  'docs/contracts/turn-feedback-v1.schema.json',
  'docs/layered-turn-feedback.md',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/game/panaderia.blade.php',
  'tests/Feature/Feedback/TurnFeedbackTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [assessor, resolver, composer, controller, request, config, schemaSource, docs, dialogue, view, routes] = await Promise.all([
  read(paths[3]), read(paths[4]), read(paths[2]), read(paths[5]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read(paths[10]), read(paths[11]), read('routes/web.php'),
]);
const schema = JSON.parse(schemaSource);
const combinedRuntime = `${assessor}${resolver}${composer}${controller}${request}${dialogue}`;

assert(routes.includes("Route::post('/spelen/madrid/la-panaderia/feedback'"), 'De feedbackroute ontbreekt');
assert(routes.includes('throttle:turn-feedback'), 'De feedbackroute moet rate limiting gebruiken');
assert(resolver.includes('PublishedContentRepository') && resolver.includes('latestProductionItem'), 'Feedbackcontext moet uit de exacte productie-release komen');
assert(assessor.includes("'/chat/completions'") && assessor.includes("'strict' => true"), 'De beoordelaar moet strikt gestructureerde OpenAI-output gebruiken');
assert(assessor.includes('Validator::make') && assessor.includes('between:0,4'), 'Modeloutput moet na ontvangst server-side worden gevalideerd');
assert(assessor.includes('Tekstfeedback mag geen uitspraakclaim bevatten'), 'Vrije feedbacktekst moet uitspraakclaims blokkeren');
assert(!assessor.includes("'pronunciation' =>"), 'De tekstbeoordelaar mag geen uitspraakscore vragen');
assert(schema.properties?.data?.properties?.rubric?.properties?.pronunciation?.$ref === '#/$defs/unassessed_pronunciation', 'Uitspraak moet verplicht niet beoordeeld blijven');
assert(schema.$defs?.unassessed_pronunciation?.properties?.score?.type === 'null', 'Uitspraakscore moet null zijn');
assert(schema.properties?.meta?.properties?.progress_affecting?.const === false, 'Feedback mag voortgang niet beïnvloeden');
assert(schema.properties?.meta?.properties?.audio_assessed?.const === false, 'Het contract mag geen audio-evidence claimen');
assert(composer.includes('pronunciation_included') && composer.includes('/ .875'), 'De totaalscore moet zonder uitspraak worden genormaliseerd');
assert(controller.includes("'answer_persisted_server_side' => false"), 'Serverretentie moet expliciet uit staan');
assert(request.includes("'max:300'") && request.includes('typed_assist'), 'Feedbackinvoer moet begrensd en bronbewust zijn');
assert(dialogue.includes('pendingStateBeforeTurn') && dialogue.includes('retrySuccessfulTurn'), 'Veilige rollback-herkansing ontbreekt');
assert(dialogue.includes("payload?.meta?.progress_affecting !== false") && dialogue.includes("status !== 'not_assessed'"), 'De client moet het veilige responscontract controleren');
assert(view.includes('data-feedback-details') && view.includes('data-feedback-retry'), 'Compacte en uitgebreide feedbacklagen ontbreken');
assert(config.includes('gpt-4o-mini') && docs.includes('Modeloutput bepaalt nooit'), 'Modelconfiguratie of vertrouwensgrens is niet gedocumenteerd');
assert(!combinedRuntime.includes('ffmpeg'), 'Fase 2D mag geen ffmpeg-conversie introduceren');

console.log('Gelaagde feedback geldig: productiecontext, servervalidatie, communicatieve rubric, geen uitspraakclaim en veilige rollback-herkansing.');
