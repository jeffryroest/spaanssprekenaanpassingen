import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/PlayerProgress/CompletePanaderiaMission.php',
  'app/Http/Controllers/Game/CompletePanaderiaMissionController.php',
  'app/Http/Controllers/PlayerProgressController.php',
  'app/Http/Requests/Game/CompletePanaderiaMissionRequest.php',
  'app/PlayerProgress/PanaderiaMissionDefinition.php',
  'database/migrations/2026_08_16_090000_create_player_progress_tables.php',
  'docs/account-progress.md',
  'docs/contracts/panaderia-completion-v1.schema.json',
  'docs/contracts/player-progress-v1.schema.json',
  'resources/js/game/panaderia-dialogue.js',
  'resources/views/player/progress.blade.php',
  'tests/Feature/PlayerProgressTest.php',
];
await Promise.all(paths.map((path) => access(new URL(path, root))));

const [action, controller, request, definition, publishedMission, migration, docs, completionSchemaSource, progressSchemaSource, dialogue, routes] = await Promise.all([
  read(paths[0]), read(paths[1]), read(paths[3]), read(paths[4]), read('app/PlayerProgress/PublishedPanaderiaMission.php'), read(paths[5]), read(paths[6]), read(paths[7]), read(paths[8]), read(paths[9]), read('routes/web.php'),
]);
const completionSchema = JSON.parse(completionSchemaSource);
const progressSchema = JSON.parse(progressSchemaSource);
const runtime = `${action}${controller}${request}${definition}${dialogue}`;

assert(completionSchema.additionalProperties === false, 'Het completioncontract moet onbekende hoofdvelden blokkeren');
assert(completionSchema.properties?.turns?.minItems === 5 && completionSchema.properties?.turns?.maxItems === 5, 'Het completioncontract moet exact vijf beurten eisen');
assert(completionSchema.properties?.turns?.items?.additionalProperties === false, 'Beurtbewijs mag geen antwoord- of transcriptvelden dragen');
assert(progressSchema.properties?.meta?.properties?.audio_persisted?.const === false, 'Het voortgangscontract moet audioserverretentie uitsluiten');
assert(routes.includes("Route::post('/spelen/madrid/la-panaderia/voltooien'"), 'De accountopslagroute ontbreekt');
assert(routes.includes('throttle:mission-completions') && routes.includes("Route::middleware('auth')"), 'Accountopslag moet authenticatie en rate limiting gebruiken');
assert(definition.includes('self::MAX_XP') && publishedMission.includes('latestProductionItem'), 'Beloningen moeten server-side begrensd en uit gepubliceerde content afgeleid zijn');
assert(action.includes('lockForUpdate') && action.includes('DB::transaction') && action.includes('completion_key'), 'De beloningstransactie of idempotencygrens ontbreekt');
assert(migration.includes("Schema::create('game_ledger'") && migration.includes("->unique()"), 'Het append-only idempotente ledger ontbreekt');
assert(request.includes("'answer' => ['prohibited']") && request.includes("'transcript' => ['prohibited']"), 'Gevoelige leerinhoud moet door de requestgrens worden geweigerd');
assert(controller.includes("'audio_persisted' => false") && controller.includes("'feedback_persisted' => false"), 'Privacymetadata ontbreekt in de opslagrespons');
assert(dialogue.includes('completionKey') && dialogue.includes('syncAccountProgress'), 'De client mist hervatbare accountopslag');
assert(!action.includes('answer') && !action.includes('transcript') && !action.includes('feedback'), 'De voortgangsactie mag geen antwoord, transcript of feedback verwerken');
assert(docs.includes('tijdelijke runtimeprojectie') && docs.includes('cryptografisch gekoppelde beurtbewijzen'), 'De architectuurbrug en resterende vertrouwensgrens moeten expliciet zijn');
assert(!runtime.includes('ffmpeg'), 'Accountvoortgang mag geen mediaconversie introduceren');

console.log('Accountvoortgang geldig: productiegebonden routebewijs, serverbeloningen, idempotent ledger, duurzame projectie en geen gevoelige leerinhoud.');
