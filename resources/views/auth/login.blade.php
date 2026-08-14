<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Log in op de Content Studio van Spaansspreken.nl.">
    <meta name="theme-color" content="#172033">
    <title>Inloggen · Content Studio · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-700 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(30rem,0.95fr)]" data-auth-shell>
        <section class="relative hidden overflow-hidden bg-studio-900 p-12 text-white lg:flex lg:flex-col lg:justify-between xl:p-16" aria-labelledby="studio-intro-title">
            <div aria-hidden="true" class="absolute -left-24 -top-24 size-96 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div aria-hidden="true" class="absolute -bottom-40 -right-32 size-[32rem] rounded-full border-[5rem] border-white/[0.03]"></div>

            <a href="{{ route('home') }}" class="relative flex w-fit items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-400">
                <span class="grid size-11 place-items-center rounded-xl bg-brand-500 text-xl font-black shadow-lg shadow-black/20" aria-hidden="true">S</span>
                <span class="text-lg font-bold tracking-tight">Spaansspreken<span class="text-brand-400">.nl</span></span>
            </a>

            <div class="relative max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-300">Content Studio</p>
                <h1 id="studio-intro-title" class="mt-4 text-4xl font-bold leading-tight tracking-tight xl:text-5xl">Leercontent veilig maken, beoordelen en publiceren.</h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-slate-300">Eén centrale werkruimte voor de Spaanse leerwereld van Spaansspreken.nl, met versiebeheer en gecontroleerde bevoegdheden.</p>

                <ul class="mt-10 grid gap-4 text-sm text-slate-200 sm:grid-cols-2">
                    @foreach (['Canonieke contentbron', 'Onveranderlijke revisies', 'Rollen en bevoegdheden', 'Veilige publicatieroute'] as $benefit)
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-emerald-400/15 text-emerald-300" aria-hidden="true">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 10 3 3 7-7" /></svg>
                            </span>
                            {{ $benefit }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-slate-500">Beveiligde beheeromgeving · Spaansspreken.nl</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12" aria-labelledby="login-title">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-10 flex w-fit items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 lg:hidden">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-500 text-lg font-black text-white shadow-md" aria-hidden="true">S</span>
                    <span class="font-bold tracking-tight text-slate-900">Spaansspreken<span class="text-brand-600">.nl</span></span>
                </a>

                <p class="cs-eyebrow">Welkom terug</p>
                <h2 id="login-title" class="mt-3 text-3xl font-bold tracking-tight text-slate-950">Inloggen op de Content Studio</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">Gebruik het account waaraan een redactierol is toegewezen.</p>

                @if ($errors->any())
                    <div class="cs-alert-error mt-6" role="alert">
                        <p class="font-bold">Inloggen is niet gelukt.</p>
                        <p class="mt-1">Controleer je gegevens en probeer het opnieuw.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" novalidate>
                    @csrf

                    <div>
                        <label for="email" class="cs-label">E-mailadres</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="cs-field min-h-12 {{ $errors->has('email') ? 'cs-field-error' : '' }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')<p id="email-error" class="cs-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="cs-label">Wachtwoord</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="cs-field min-h-12 {{ $errors->has('password') ? 'cs-field-error' : '' }}" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        @error('password')<p id="password-error" class="cs-error" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex w-fit items-center gap-3 text-sm font-medium text-slate-600">
                        <input name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Ingelogd blijven
                    </label>

                    <button type="submit" class="cs-button-primary min-h-12 w-full">Inloggen</button>
                </form>

                <div class="mt-8 flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-xs leading-5 text-slate-500 shadow-sm">
                    <x-content-studio.icon name="shield" class="mt-0.5 size-4 shrink-0 text-emerald-600" />
                    Toegang en acties worden server-side gecontroleerd. Deel je inloggegevens nooit met anderen.
                </div>
            </div>
        </section>
    </main>
</body>
</html>
