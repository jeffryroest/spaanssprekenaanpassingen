<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#172033">
    <title>Geen toegang · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-700 antialiased">
    <main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-5 py-12">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-brand-50 to-transparent"></div>

        <section class="cs-panel relative w-full max-w-2xl overflow-hidden text-center" aria-labelledby="error-title">
            <div class="border-b border-slate-100 p-8 sm:p-10">
                <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-red-50 text-red-700 shadow-sm">
                    <x-content-studio.icon name="shield" class="size-8" />
                </span>
                <p class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-red-600">HTTP 403 · Toegang geweigerd</p>
                <h1 id="error-title" class="mx-auto mt-3 max-w-lg text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Je hebt geen toegang tot de Content Studio</h1>
                <p class="mx-auto mt-4 max-w-lg text-sm leading-6 text-slate-500 sm:text-base">Je bent ingelogd, maar aan dit account is nog geen bevoegde redactierol toegewezen.</p>
            </div>

            <div class="flex flex-col-reverse justify-center gap-3 bg-slate-50 px-6 py-5 sm:flex-row">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cs-button-secondary w-full sm:w-auto">
                            <x-content-studio.icon name="logout" class="size-4" />
                            Uitloggen
                        </button>
                    </form>
                @endauth
                <a href="{{ route('home') }}" class="cs-button-primary">Terug naar de startpagina</a>
            </div>
        </section>
    </main>
</body>
</html>
