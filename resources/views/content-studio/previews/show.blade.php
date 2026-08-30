<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Preview · {{ $localization['title'] ?? $contentNode->slug }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100" data-content-preview data-preview-scene="{{ $domainData['scene'] }}">
    <header class="sticky top-0 z-50 border-b border-amber-300/30 bg-amber-300 text-slate-950 shadow-lg">
        <div class="mx-auto flex max-w-[100rem] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em]">Niet-productiepreview</p>
                <p class="mt-0.5 text-sm font-semibold">{{ $localization['title'] ?? $contentNode->slug }} · revisie {{ $revision->version }} · schrijft geen voortgang</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-lg border border-slate-900/20 bg-white/40 p-1" aria-label="Previewbreedte">
                    @foreach([['mobile', 'Mobiel'], ['tablet', 'Tablet'], ['desktop', 'Desktop']] as [$device, $label])
                        <button type="button" data-preview-device="{{ $device }}" class="rounded-md px-3 py-2 text-xs font-bold hover:bg-white/70 focus:outline-none focus:ring-2 focus:ring-slate-900" @if($device === 'desktop') aria-pressed="true" @else aria-pressed="false" @endif>{{ $label }}</button>
                    @endforeach
                </div>
                <a href="{{ route('content-studio.content.show', $contentNode) }}" class="inline-flex min-h-10 items-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white focus:outline-none focus:ring-2 focus:ring-white">Preview sluiten</a>
            </div>
        </div>
    </header>

    <main class="px-3 py-6 sm:px-6">
        @if($inspection['errors'] !== [])
            <section class="mx-auto mb-5 max-w-4xl rounded-xl border border-red-300 bg-red-950 p-4 text-sm text-red-100" role="alert">
                <p class="font-bold">Deze revisie bevat blokkerende routefouten.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($inspection['errors'] as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </section>
        @endif

        <div data-preview-frame data-preview-width="desktop" class="preview-frame mx-auto overflow-hidden rounded-3xl bg-[#f5ecdc] text-[#22342f] shadow-2xl shadow-black/40 transition-[max-width] duration-300">
            @if($mediaByRole->get('ambient_audio'))
                @php($ambientAudio = $mediaByRole->get('ambient_audio'))
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#dec99f] bg-[#fffaf0] px-5 py-3 text-sm">
                    <span class="font-bold text-[#53675e]">Optioneel omgevingsgeluid · standaard uit</span>
                    <audio controls preload="none" class="h-9 max-w-full"><source src="{{ route('content-studio.media.stream', $ambientAudio) }}" type="{{ $ambientAudio->mime_type }}"></audio>
                </div>
            @endif
            @if($domainData['scene'] === 'madrid_hub')
                @php($background = $mediaByRole->get('map_background'))
                <section class="preview-world relative isolate min-h-[680px] overflow-hidden p-6 sm:p-10" @if($background) style="background-image: linear-gradient(rgb(23 44 54 / 25%), rgb(23 44 54 / 5%)), url('{{ route('content-studio.media.stream', $background) }}')" @endif>
                    <div class="relative z-10 max-w-xl rounded-3xl bg-[#fffaf0]/95 p-6 shadow-xl backdrop-blur sm:p-8">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#a8432b]">{{ data_get($domainData, 'intro.eyebrow') }}</p>
                        <h1 class="mt-3 text-3xl font-black tracking-tight text-[#172c36] sm:text-4xl">{{ data_get($domainData, 'intro.title') }}</h1>
                        <p class="mt-4 leading-7 text-[#4c6259]">{{ data_get($domainData, 'intro.description') }}</p>
                        <p class="mt-5 rounded-2xl bg-[#f2e4c6] p-4 text-sm font-bold text-[#694124]">Doel: {{ data_get($domainData, 'intro.objective') }}</p>
                    </div>

                    <div class="relative z-10 mt-8 min-h-[390px] rounded-3xl border border-white/60 bg-[#d9b98f]/45 shadow-inner backdrop-blur-sm" aria-label="Interactieve kaartpreview">
                        @foreach($domainData['hotspots'] as $hotspot)
                            <button type="button" class="absolute max-w-40 -translate-x-1/2 -translate-y-1/2 rounded-2xl border-2 px-3 py-2 text-left text-xs font-black shadow-lg transition hover:scale-105 focus:outline-none focus:ring-4 focus:ring-amber-300 {{ $hotspot['state'] === 'open' ? 'border-[#d65f31] bg-[#fff9ed] text-[#7f331f]' : 'border-slate-300 bg-slate-100 text-slate-600' }}" style="left: {{ data_get($hotspot, 'position.x') }}%; top: {{ data_get($hotspot, 'position.y') }}%;" data-preview-hotspot="{{ $hotspot['id'] }}" data-preview-description="{{ $hotspot['description'] }}" data-preview-state="{{ $hotspot['state'] }}">
                                <span lang="es">{{ data_get($hotspot, 'label.es') }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div data-preview-world-feedback class="relative z-10 mt-4 rounded-2xl bg-[#fffaf0] p-4 text-sm font-semibold text-[#53675e] shadow-sm" aria-live="polite">Kies een hotspot om de redactietekst en toestand te controleren.</div>

                    <div class="relative z-10 mt-6 grid gap-3 sm:grid-cols-3">
                        @foreach($domainData['inspectables'] as $inspectable)
                            <button type="button" class="rounded-2xl border border-[#d9c49c] bg-[#fffaf0] p-4 text-left shadow-sm hover:border-[#d65f31] focus:outline-none focus:ring-4 focus:ring-amber-300" data-preview-inspectable="{{ $inspectable['id'] }}">
                                <span class="block text-xs font-black uppercase tracking-wide text-[#a8432b]">{{ $inspectable['kind'] }}</span>
                                <span class="mt-2 block font-black text-[#172c36]">{{ $inspectable['title'] }}</span>
                                <span class="mt-1 block text-sm leading-5 text-[#5d6e66]">{{ $inspectable['body'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @else
                @php($sceneBackground = $mediaByRole->get('scene_background'))
                @php($npcPortrait = $mediaByRole->get('npc_portrait'))
                <section class="preview-dialogue-scene min-h-[720px] p-5 sm:p-10" @if($sceneBackground) style="background-image: linear-gradient(rgb(23 44 54 / 48%), rgb(23 44 54 / 22%)), url('{{ route('content-studio.media.stream', $sceneBackground) }}')" @endif>
                    <div class="mx-auto max-w-4xl">
                        <div class="grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
                            <aside class="rounded-3xl bg-[#17313a] p-6 text-white shadow-xl">
                                @if($npcPortrait)
                                    <img src="{{ route('content-studio.media.stream', $npcPortrait) }}" alt="{{ $npcPortrait->alt_text }}" class="aspect-square w-full rounded-2xl object-cover">
                                @else
                                    <div class="grid aspect-square w-full place-items-center rounded-2xl bg-[#264b50] text-7xl" aria-hidden="true">💬</div>
                                @endif
                                <p class="mt-5 text-xs font-black uppercase tracking-[0.16em] text-[#f4b94f]">{{ data_get($domainData, 'npc.role.nl') }}</p>
                                <h1 class="mt-2 text-2xl font-black">{{ data_get($domainData, 'npc.name') }}</h1>
                                <p class="mt-3 text-sm leading-6 text-[#d8e5df]">{{ data_get($domainData, 'npc.description') }}</p>
                            </aside>

                            <div class="rounded-3xl bg-[#fffaf0]/95 p-5 shadow-xl backdrop-blur sm:p-7">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-[#a8432b]">Gesprekspreview</p>
                                        <h2 class="mt-2 text-2xl font-black text-[#172c36]" lang="es">{{ data_get($domainData, 'mission.title.es') }}</h2>
                                        <p class="mt-2 text-sm leading-6 text-[#53675e]">{{ data_get($domainData, 'mission.objective') }}</p>
                                    </div>
                                    <label class="text-xs font-bold text-[#53675e]">Niveau
                                        <select data-preview-level class="mt-1 block rounded-lg border border-[#cfb990] bg-white px-3 py-2 text-sm font-bold">
                                            <option>A0</option><option>A1</option><option>A2</option>
                                        </select>
                                    </label>
                                </div>

                                @if($domainData['scene'] === 'health_text_dialogue')
                                    <details class="mt-5 rounded-2xl border border-[#e4c47b] bg-[#fff4cf] p-4">
                                        <summary class="cursor-pointer font-black text-[#70431f]">{{ data_get($domainData, 'roleplay.title.nl') }}</summary>
                                        <p class="mt-3 text-sm leading-6 text-[#755633]">{{ data_get($domainData, 'roleplay.description') }}</p>
                                        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-[#755633]">
                                            @foreach(data_get($domainData, 'roleplay.facts', []) as $fact)<li><span lang="es">{{ $fact['es'] }}</span> — {{ $fact['nl'] }}</li>@endforeach
                                        </ul>
                                    </details>
                                @endif

                                <div data-preview-step class="mt-6" aria-live="polite"></div>
                                <div class="mt-5">
                                    <label for="preview-answer" class="block text-sm font-black text-[#283d35]">Simuleer een spelersantwoord</label>
                                    <textarea id="preview-answer" data-preview-answer rows="3" class="mt-2 block w-full rounded-xl border border-[#cfb990] bg-white p-3 text-sm text-[#1f342d] focus:outline-none focus:ring-4 focus:ring-amber-300" placeholder="Typ een Spaans antwoord…"></textarea>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button" data-preview-submit class="rounded-xl bg-[#d65f31] px-4 py-3 text-sm font-black text-white focus:outline-none focus:ring-4 focus:ring-amber-300">Antwoord simuleren</button>
                                        <button type="button" data-preview-example class="rounded-xl border border-[#cfb990] bg-white px-4 py-3 text-sm font-black text-[#6b4429] focus:outline-none focus:ring-4 focus:ring-amber-300">Voorbeeld invullen</button>
                                        <button type="button" data-preview-reset class="rounded-xl px-4 py-3 text-sm font-black text-[#53675e] focus:outline-none focus:ring-4 focus:ring-amber-300">Opnieuw</button>
                                    </div>
                                </div>
                                <div data-preview-feedback class="mt-5" aria-live="polite"></div>
                                <ol data-preview-history class="mt-5 space-y-2 border-t border-[#ead8b8] pt-5 text-sm"></ol>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </main>

    <script type="application/json" data-preview-domain-data>@json($domainData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
</body>
</html>
