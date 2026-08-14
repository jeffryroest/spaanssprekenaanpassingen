import { readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

async function readJson(relativePath) {
  return JSON.parse(await readFile(new URL(relativePath, root), 'utf8'));
}

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function assertUnique(items, label) {
  const seen = new Set();
  for (const item of items) {
    assert(item?.id, `${label} bevat een item zonder id`);
    assert(!seen.has(item.id), `${label} bevat dubbel id: ${item.id}`);
    seen.add(item.id);
  }
  return seen;
}

const seed = await readJson('content/seeds/madrid-panaderia.json');
const importSchema = await readJson('contracts/import-record.schema.json');

assert(seed.status === 'draft', 'Seedcontent moet als draft beginnen');
assert(seed.source?.origin === 'spaansspreken-content-studio', 'Content Studio moet de bron van waarheid zijn');
assert(seed.speech?.recording?.ffmpegConversion === false, 'WebM mag niet stilzwijgend via ffmpeg worden geconverteerd');
assert(seed.mission?.spokenCompletionRequirements?.minimumSpeechTurns >= 1, 'De missie moet verplichte spreekbeurten bevatten');

const stepIds = assertUnique(seed.mission.steps, 'Missiestappen');
assertUnique(seed.npcs, 'NPCs');
assertUnique(seed.vocabulary, 'Woordenschat');
assertUnique(seed.phrases, 'Zinnen');
assertUnique(seed.learningObjectives, 'Leerdoelen');
assert(seed.rewards?.id, 'Beloningsconfiguratie moet een id hebben');

for (const step of seed.mission.steps) {
  if (step.next) {
    assert(stepIds.has(step.next), `Onbekende next-verwijzing vanuit ${step.id}: ${step.next}`);
  }
  for (const variant of step.variants ?? []) {
    assert(stepIds.has(variant), `Onbekende variant vanuit ${step.id}: ${variant}`);
  }
}

const rubricWeight = seed.speech.evaluation.dimensions.reduce((sum, dimension) => sum + dimension.weight, 0);
assert(Math.abs(rubricWeight - 1) < 0.000001, `Rubricgewichten tellen op tot ${rubricWeight}, niet tot 1`);

assert(importSchema.type === 'object', 'Importcontract moet één recordobject beschrijven');
assert(importSchema.required?.includes('source_name'), 'Importcontract moet bronherkomst verplichten');
assert(importSchema.required?.includes('payload'), 'Importcontract moet een payload verplichten');

console.log('Fundament geldig: importschema en Madrid/bakkerij-seed zijn consistent.');
