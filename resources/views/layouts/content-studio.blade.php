<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Beveiligde Content Studio van Spaansspreken.nl.">
    <title>@yield('title', 'Content Studio') · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-white/10 bg-stone-950/90">
            <div class="mx-auto max-w-7xl px-6 py-5 lg:px-10">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div>
                        <a href="{{ route('content-studio.dashboard') }}" class="text-lg font-semibold text-white">
                            Spaansspreken<span class="text-orange-400">.nl</span>
                        </a>
                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-stone-400">Content Studio</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-stone-400">{{ auth()->user()->content_role->label() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-stone-200 transition hover:border-white/20 hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">
                                Uitloggen
                            </button>
                        </form>
                    </div>
                </div>

                <nav class="mt-5 flex flex-wrap gap-2" aria-label="Content Studio-navigatie">
                    <a href="{{ route('content-studio.dashboard') }}"
                       @if (request()->routeIs('content-studio.dashboard')) aria-current="page" @endif
                       class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('content-studio.dashboard') ? 'bg-orange-400 text-stone-950' : 'text-stone-300 hover:bg-white/[0.06] hover:text-white' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('content-studio.content.index') }}"
                       @if (request()->routeIs('content-studio.content.*')) aria-current="page" @endif
                       class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('content-studio.content.*') ? 'bg-orange-400 text-stone-950' : 'text-stone-300 hover:bg-white/[0.06] hover:text-white' }}">
                        Contentcatalogus
                    </a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-10 lg:px-10">
            @if (session('success'))
                <div class="mb-8 rounded-xl border border-emerald-300/20 bg-emerald-300/10 px-5 py-4 text-sm text-emerald-100" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
