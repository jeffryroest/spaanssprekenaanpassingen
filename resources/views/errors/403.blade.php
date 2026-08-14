<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Geen toegang · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <section class="w-full max-w-xl rounded-3xl border border-white/10 bg-white/[0.06] p-8 text-center shadow-2xl shadow-black/30 sm:p-10" aria-labelledby="error-title">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">HTTP 403</p>
            <h1 id="error-title" class="mt-3 text-3xl font-bold text-white">Je hebt geen toegang tot de Content Studio</h1>
            <p class="mt-4 leading-7 text-stone-300">Je bent ingelogd, maar er is nog geen bevoegde redactierol aan dit account toegewezen.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="rounded-xl bg-orange-500 px-5 py-3 font-semibold text-white transition hover:bg-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300">
                    Terug naar de startpagina
                </a>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-white/10 px-5 py-3 font-semibold text-stone-200 transition hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                            Uitloggen
                        </button>
                    </form>
                @endauth
            </div>
        </section>
    </main>
</body>
</html>
