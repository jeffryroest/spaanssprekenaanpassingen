import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);
const read = (path) => readFile(new URL(path, root), 'utf8');
const assert = (condition, message) => {
  if (!condition) throw new Error(message);
};

const paths = [
  'app/Http/Controllers/Auth/RegisteredUserController.php',
  'app/Http/Controllers/Auth/PasswordResetLinkController.php',
  'app/Http/Controllers/Auth/NewPasswordController.php',
  'app/Http/Controllers/PlayerAccountController.php',
  'app/Http/Controllers/ContentStudio/PlayerAccountOverviewController.php',
  'resources/views/layouts/player-auth.blade.php',
  'resources/views/auth/register.blade.php',
  'resources/views/auth/forgot-password.blade.php',
  'resources/views/auth/reset-password.blade.php',
  'resources/views/player/account.blade.php',
  'resources/views/content-studio/accounts/index.blade.php',
  'tests/Feature/PlayerAccountLifecycleTest.php',
  'tests/Feature/ContentStudioPlayerAccountsTest.php',
  'docs/player-auth-account.md',
];

await Promise.all(paths.map((path) => access(new URL(path, root))));

const [registration, resetLink, resetPassword, account, accountOverview, authLayout, registerView, accountView, accountsView, tests, adminTests, routes, provider, user, roadmap] = await Promise.all([
  read(paths[0]),
  read(paths[1]),
  read(paths[2]),
  read(paths[3]),
  read(paths[4]),
  read(paths[5]),
  read(paths[6]),
  read(paths[9]),
  read(paths[10]),
  read(paths[11]),
  read(paths[12]),
  read('routes/web.php'),
  read('app/Providers/AppServiceProvider.php'),
  read('app/Models/User.php'),
  read('docs/roadmap.md'),
]);

assert(routes.includes("'/inloggen'") && routes.includes("'/aanmelden'") && routes.includes("'/wachtwoord-vergeten'"), 'De Nederlandse spelersentree is niet volledig gerouteerd');
assert(routes.includes("Route::redirect('/login', '/inloggen'") && routes.includes("Route::redirect('/register', '/aanmelden'"), 'De veilige legacy-redirects ontbreken');
assert(registration.includes('Password::min(12)->letters()->numbers()') && registration.includes('Auth::login($user)'), 'Registratie mist wachtwoordbeleid of automatische login');
assert(resetLink.includes('Password::sendResetLink') && resetLink.includes('Als dit e-mailadres bij ons bekend is'), 'Wachtwoordherstel verraadt accountstatus of gebruikt de broker niet');
assert(resetPassword.includes('Password::reset') && resetPassword.includes('PasswordReset($user)'), 'Het geldige tokenpad voor wachtwoordherstel ontbreekt');
assert(account.includes("'current_password:web'") && account.includes("'Cache-Control' => 'private, no-store'"), 'Speleraccount mist herauthenticatie of private caching');
assert(provider.includes("'accounts.manage'") && accountOverview.includes("Gate::authorize('accounts.manage')"), 'Het supportoverzicht is niet exclusief voor beheerders');
assert(accountsView.includes('Zoeken gebeurt via POST') && !accountsView.includes('transcript_text') && !accountsView.includes('audio_path'), 'De supportweergave bewaakt de privacygrens niet');
assert(user.includes('latestSubscription') && accountView.includes('Proefweek en abonnement'), 'De accountstatus is niet verbonden met toegang en abonnement');
assert(authLayout.includes('madrid-morning.webp') && registerView.includes('Een account maken start geen betaling'), 'De spelersgerichte blauwdruk of betaalduidelijkheid ontbreekt');
assert(tests.includes('generic_reset_response') && tests.includes('email_change_requires_current_password'), 'De spelerslevenscyclus mist regressiedekking');
assert(adminTests.includes('exclusive_to_administrators') && adminTests.includes('cannot_view_other_accounts'), 'De supportautorisatie mist regressiedekking');
assert(roadmap.includes('4B1 — spelersentree en accountbasis'), 'De roadmap is niet bijgewerkt');

console.log('Fase 4B1 geldig: spelersentree, veilig herstel, accountbasis en beheerder-only supportoverzicht.');
