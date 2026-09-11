import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Actions/Accounts/ErasePlayerAccount.php',
  'app/Actions/ContentStudio/AssignContentRole.php',
  'app/Http/Controllers/AccountDeletionRequestController.php',
  'app/Http/Controllers/ContentStudio/PlayerAccountDetailController.php',
  'app/Models/AccountDeletionRequest.php',
  'app/Models/AccountSupportNote.php',
  'config/privacy.php',
  'database/migrations/2026_09_10_220000_add_account_support_and_deletion_workflow.php',
  'resources/views/content-studio/accounts/show.blade.php',
  'tests/Feature/AccountSupportLifecycleTest.php',
  'docs/account-support-retention.md',
];

await Promise.all(paths.map((path) => access(new URL(path, root))));

const [eraser, roleAction, playerController, adminController, migration, accountView, adminView, privacyView, tests, routes, roadmap] = await Promise.all([
  read(paths[0]),
  read(paths[1]),
  read(paths[2]),
  read(paths[3]),
  read(paths[7]),
  read('resources/views/player/account.blade.php'),
  read(paths[8]),
  read('resources/views/privacy.blade.php'),
  read(paths[9]),
  read('routes/web.php'),
  read('docs/roadmap.md'),
]);

assert(routes.includes('/account/verwijderverzoek') && routes.includes('accounts.deletions.process'), 'Het dubbel bevestigde verwijderproces is niet volledig gerouteerd');
assert(routes.includes('accounts.role.update') && routes.includes('accounts.notes.store'), 'De beheer- en supportroutes ontbreken');
assert(roleAction.includes('?ContentRole $role') && roleAction.includes('content_role_audits') === false && roleAction.includes('De laatste beheerder'), 'Rolintrekking of beheerdersbescherming ontbreekt');
assert(playerController.includes("'current_password:web'") && playerController.includes('confirm_deletion'), 'Het spelersverzoek mist herauthenticatie of expliciete bevestiging');
assert(adminController.includes("'current_password:web'") && adminController.includes('confirmation_email'), 'Definitieve verwerking mist dubbele beheerdersbevestiging');
assert(eraser.includes("DB::table('mission_attempts')") && eraser.includes('billingRetentionDate') && !eraser.includes("DB::table('subscription_orders')->delete"), 'De wisactie scheidt speldata en fiscale gegevens niet');
assert(migration.includes('account_support_notes') && migration.includes('account_deletion_requests') && migration.includes('privacy_erased_at'), 'De support- en verwijderstatus is niet duurzaam gemodelleerd');
assert(accountView.includes('Verwijderverzoek indienen') && adminView.includes('Neem geen antwoorden, transcripties, audio of AI-feedback over'), 'De gebruikersinterface bewaakt de bevestiging of privacygrens niet');
assert(privacyView.includes('zeven jaar') && roadmap.includes('4B2 — beheer en support (gerealiseerd)'), 'Privacy-uitleg of roadmap is niet bijgewerkt');
assert(tests.includes('retains_billing_until_the_fiscal_deadline') && tests.includes('active_subscription_blocks_erasure'), 'De kritieke retentie- en abonnementsgrenzen missen regressiedekking');

console.log('Fase 4B2 geldig: geaudite rollen, minimale support en gecontroleerde accountwissing met fiscale retentie.');
