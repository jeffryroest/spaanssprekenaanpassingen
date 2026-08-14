<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Log in op de Content Studio van Spaansspreken.nl.">
    <title>Inloggen · Content Studio · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-6 py-12">
        <div aria-hidden="true" class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(234,88,12,0.3),_transparent_36%),radial-gradient(circle_at_bottom_right,_rgba(30,64,175,0.25),_transparent_38%)]"></div>

        <section class="relative w-full max-w-md rounded-3xl border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-black/30 backdrop-blur sm:p-9" aria-labelledby="login-title">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-white">
                Spaansspreken<span class="text-orange-400">.nl</span>
            </a>

            <p class="mt-8 text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Content Studio</p>
            <h1 id="login-title" class="mt-2 text-3xl font-bold tracking-tight text-white">Veilig inloggen</h1>
            <p class="mt-3 text-sm leading-6 text-stone-300">Deze beheeromgeving is alleen toegankelijk voor toegewezen redactierollen.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-stone-200">E-mailadres</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                           class="mt-2 w-full rounded-xl border border-white/10 bg-stone-900/80 px-4 py-3 text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/30"
                           aria-describedby="email-error">
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-stone-200">Wachtwoord</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="mt-2 w-full rounded-xl border border-white/10 bg-stone-900/80 px-4 py-3 text-white outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-400/30"
                           aria-describedby="password-error">
                    @error('password')
                        <p id="password-error" class="mt-2 text-sm text-red-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-3 text-sm text-stone-300">
                    <input name="remember" type="checkbox" value="1" class="size-4 rounded border-white/20 bg-stone-900 text-orange-500 focus:ring-orange-400">
                    Ingelogd blijven
                </label>

                <button type="submit" class="w-full rounded-xl bg-orange-500 px-5 py-3 font-semibold text-white transition hover:bg-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 focus:ring-offset-stone-950">
                    Inloggen
                </button>
            </form>
        </section>
    </main>
</body>
</html>
