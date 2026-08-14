<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Beveiligde Content Studio van Spaansspreken.nl.">
    <meta name="theme-color" content="#172033">
    <title>@yield('title', 'Content Studio') · Spaansspreken.nl</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-700 antialiased">
    <a href="#main-content" class="cs-skip-link">Direct naar de inhoud</a>

    <button type="button" class="fixed inset-0 z-40 hidden bg-studio-950/55 backdrop-blur-[2px] lg:hidden" data-sidebar-overlay aria-label="Navigatiemenu sluiten"></button>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-studio-900 text-white shadow-2xl shadow-slate-950/20 transition-transform duration-300 lg:translate-x-0" data-studio-sidebar aria-label="Hoofdnavigatie">
        <div class="flex h-20 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('content-studio.dashboard') }}" class="flex items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-400">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-500 text-lg font-black text-white shadow-lg shadow-black/30" aria-hidden="true">S</span>
                <span>
                    <span class="block text-base font-bold tracking-tight">Spaansspreken<span class="text-brand-400">.nl</span></span>
                    <span class="mt-0.5 block text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-slate-400">Content Studio</span>
                </span>
            </a>
            <button type="button" class="grid size-10 place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-400 lg:hidden" data-sidebar-toggle aria-expanded="false" aria-controls="studio-sidebar-nav">
                <span class="sr-only">Navigatiemenu sluiten</span>
                <x-content-studio.icon name="close" />
            </button>
        </div>

        <nav id="studio-sidebar-nav" class="flex-1 overflow-y-auto px-4 py-6" aria-label="Content Studio">
            <p class="px-3 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-500">Werkruimte</p>
            <div class="mt-2 space-y-1">
                <a href="{{ route('content-studio.dashboard') }}"
                   @if (request()->routeIs('content-studio.dashboard')) aria-current="page" @endif
                   class="cs-nav-link {{ request()->routeIs('content-studio.dashboard') ? 'cs-nav-link-active' : '' }}">
                    <x-content-studio.icon name="dashboard" />
                    Dashboard
                </a>
                <a href="{{ route('content-studio.content.index') }}"
                   @if (request()->routeIs('content-studio.content.*')) aria-current="page" @endif
                   class="cs-nav-link {{ request()->routeIs('content-studio.content.*') ? 'cs-nav-link-active' : '' }}">
                    <x-content-studio.icon name="catalog" />
                    Contentcatalogus
                </a>
            </div>

            <p class="mt-8 px-3 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-500">Workflow</p>
            <div class="mt-2 space-y-1">
                @can('content-studio.review')
                    <a href="{{ route('content-studio.reviews.index') }}"
                       @if (request()->routeIs('content-studio.reviews.*')) aria-current="page" @endif
                       class="cs-nav-link {{ request()->routeIs('content-studio.reviews.*') ? 'cs-nav-link-active' : '' }}">
                        <x-content-studio.icon name="review" />
                        Reviewwachtrij
                    </a>
                @else
                    <span class="cs-nav-link-disabled" aria-disabled="true">
                        <x-content-studio.icon name="review" />
                        <span class="flex-1">Reviewwachtrij</span>
                        <span class="rounded-full bg-white/[0.06] px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide text-slate-500">Alleen reviewers</span>
                    </span>
                @endcan

                @foreach ([
                    ['import', 'Importcentrum'],
                    ['release', 'Releases'],
                    ['audit', 'Auditlog'],
                    ['settings', 'Instellingen'],
                ] as [$icon, $label])
                    <span class="cs-nav-link-disabled" aria-disabled="true">
                        <x-content-studio.icon :name="$icon" />
                        <span class="flex-1">{{ $label }}</span>
                        <span class="rounded-full bg-white/[0.06] px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide text-slate-500">Straks</span>
                    </span>
                @endforeach
            </div>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-white/[0.05] p-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-500/15 text-sm font-bold text-brand-300" aria-hidden="true">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-slate-400">{{ auth()->user()->content_role->label() }}</span>
                </span>
            </div>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex h-20 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500 lg:hidden" data-sidebar-toggle aria-expanded="false" aria-controls="studio-sidebar-nav">
                        <span class="sr-only">Navigatiemenu openen</span>
                        <x-content-studio.icon name="menu" />
                    </button>

                    <nav class="min-w-0" aria-label="Kruimelpad">
                        <ol class="flex min-w-0 items-center gap-2 text-sm">
                            <li><a href="{{ route('content-studio.dashboard') }}" class="font-medium text-slate-500 hover:text-brand-700">Content Studio</a></li>
                            @if (! request()->routeIs('content-studio.dashboard'))
                                <li class="text-slate-300" aria-hidden="true">/</li>
                                <li class="truncate font-semibold text-slate-800" aria-current="page">
                                    @if (request()->routeIs('content-studio.reviews.*'))
                                        Reviewwachtrij
                                    @elseif (request()->routeIs('content-studio.content.index'))
                                        Contentcatalogus
                                    @elseif (request()->routeIs('content-studio.content.create'))
                                        Nieuw concept
                                    @elseif (request()->routeIs('content-studio.content.edit'))
                                        Bewerken
                                    @else
                                        Contentdetail
                                    @endif
                                </li>
                            @endif
                        </ol>
                    </nav>
                </div>

                <div class="relative" data-user-menu>
                    <button type="button" class="flex items-center gap-3 rounded-xl p-1.5 text-left transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500" data-user-menu-button aria-expanded="false" aria-controls="studio-user-menu">
                        <span class="grid size-9 place-items-center rounded-lg bg-brand-100 text-sm font-bold text-brand-700" aria-hidden="true">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden max-w-40 sm:block">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ auth()->user()->content_role->label() }}</span>
                        </span>
                        <x-content-studio.icon name="chevron-down" class="hidden size-4 text-slate-400 sm:block" />
                    </button>

                    <div id="studio-user-menu" class="absolute right-0 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10" role="menu">
                        <div class="border-b border-slate-100 px-3 py-2 sm:hidden">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->content_role->label() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-500" role="menuitem">
                                <x-content-studio.icon name="logout" class="size-4 text-slate-500" />
                                Uitloggen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-[100rem] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
            @if (session('success'))
                <div class="cs-alert-success mb-6" role="status">
                    <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-emerald-600 text-white" aria-hidden="true">
                        <svg class="size-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 10 3 3 7-7" /></svg>
                    </span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
