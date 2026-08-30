import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/ContentStudio/ContentReviewPolicy.php',
  'app/ContentStudio/ReviewableContent.php',
  'app/ContentStudio/DemoContentInstaller.php',
  'app/Actions/ContentStudio/WithdrawContentReview.php',
  'database/seeders/PlayableDemoContentSeeder.php',
  'database/seeders/DatabaseSeeder.php',
  'resources/views/content-studio/content/show.blade.php',
  'routes/console.php',
  'routes/web.php',
  'docs/demo-ready-content-studio.md',
  'tests/Feature/DemoContentInstallerTest.php',
];

const [policy, reviewable, installer, withdrawal, seeder, defaultSeeder, view, consoleRoutes, webRoutes, docs, tests] = paths.map(read);

assert(policy.includes("health_text_dialogue") && policy.includes("risk_tier") && policy.includes("requires_independent_reviewer"), 'Het risicobeleid houdt gevoelige inhoud niet onder onafhankelijke review');
assert(policy.includes("ContentRole::Administrator") && policy.includes("ContentRole::EditorInChief"), 'Zelfgoedkeuring is niet tot de twee bevoegde rollen beperkt');
assert(reviewable.includes("ContentType::Region") && reviewable.includes("ContentType::ConversationScenario") && reviewable.includes("PlayableDomainData"), 'De inhoudelijke reviewgrens valideert speelcontent niet opnieuw');
assert(installer.includes("PACKAGE_VERSION") && installer.includes("withTrashed") && installer.includes("conflict"), 'Het demopakket mist versie-, archief- of conflictbeveiliging');
assert(installer.includes("CreateDraftContent") && !installer.includes("ContentStatus::Published"), 'Het demopakket moet via de conceptactie lopen en mag niet publiceren');
assert(withdrawal.includes("ContentReviewAction::Withdrawn") && withdrawal.includes("content.review_withdrawn"), 'Reviewintrekking mist append-only geschiedenis of audit');
assert(consoleRoutes.includes("game:install-demo-content") && consoleRoutes.includes("--dry-run") && consoleRoutes.includes("--actor"), 'Het veilige installatiecommando is onvolledig');
assert(seeder.includes("DemoContentInstaller") && defaultSeeder.includes("environment(['local', 'testing'])"), 'De benoemde seeder of productiegrens ontbreekt');
assert(!defaultSeeder.includes("test@example.com"), 'De standaardseeder mag geen vast testaccount meer aanmaken');
assert(webRoutes.includes("withdraw-review") && view.includes("Gemotiveerde zelfgoedkeuring") && view.includes("Review intrekken"), 'De nieuwe reviewacties zijn niet volledig bedienbaar');
assert(docs.includes("nooit automatisch") && docs.includes("PUBLICEREN") && docs.includes("nooit overschreven"), 'De documentatie mist de menselijke publicatie- of overschrijfgrens');
assert(tests.includes("is_idempotent") && tests.includes("never_overwrites") && tests.includes("dry_run"), 'De installer mist regressietests voor droog draaien, idempotentie of conflicten');

console.log('Demo-ready Content Studio geldig: risicogestuurde review, inhoudelijke preflight, veilige intrekking en idempotente conceptseeding.');
