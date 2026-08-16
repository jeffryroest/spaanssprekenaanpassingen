@php
    $editing = isset($contentNode);
    $localization = $editing ? $contentNode->defaultLocalization() : null;
    $currentRevision = $editing ? $contentNode->revisions->firstWhere('version', $contentNode->current_version) : null;
    $initialDomainData = $editing
        ? data_get($currentRevision?->snapshot, 'domain_data', [])
        : ($selectedTemplate['domain_data'] ?? []);
    $domainDataJson = $initialDomainData === [] ? '' : json_encode(
        $initialDomainData,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
    $selectedContentType = $selectedTemplate['content_type'] ?? null;
@endphp

@if ($errors->any())
    <div class="cs-alert-error mb-6" role="alert">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-red-100 font-bold text-red-700" aria-hidden="true">!</span>
            <div>
                <p class="font-bold">Controleer de gemarkeerde velden.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if (! $editing)
    <section class="cs-panel mb-6 overflow-hidden" aria-labelledby="starter-title">
        <div class="cs-panel-header">
            <h2 id="starter-title" class="font-bold text-slate-900">Start met speelbare content</h2>
            <p class="mt-1 text-sm text-slate-500">Een starter vult een veilig concept in. Publiceren blijft altijd een aparte menselijke review- en releasehandeling.</p>
        </div>
        <div class="grid gap-3 p-5 md:grid-cols-3 sm:p-6">
            @foreach ($playableTemplates as $template)
                <a
                    href="{{ route('content-studio.content.create', ['template' => $template['key']]) }}"
                    class="rounded-xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-brand-500 {{ ($selectedTemplate['key'] ?? null) === $template['key'] ? 'border-brand-400 bg-brand-50 ring-1 ring-brand-300' : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/40' }}"
                >
                    <span class="block font-bold text-slate-900">{{ $template['label'] }}</span>
                    <span class="mt-1 block text-sm leading-5 text-slate-500">{{ $template['description'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

<div class="cs-panel overflow-hidden">
    <section aria-labelledby="identity-title">
        <div class="cs-panel-header">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700" aria-hidden="true">
                    <x-content-studio.icon name="document" />
                </span>
                <div>
                    <h2 id="identity-title" class="font-bold text-slate-900">Identiteit en classificatie</h2>
                    <p class="mt-1 text-sm text-slate-500">Kies hoe deze content intern herkenbaar en vindbaar wordt.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 p-5 md:grid-cols-2 sm:p-6">
            @if ($editing)
                <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
                <div>
                    <span class="cs-label">Contenttype</span>
                    <div class="mt-2 flex min-h-11 items-center rounded-lg border border-slate-200 bg-slate-50 px-3.5 text-sm font-semibold text-slate-700">
                        {{ $contentNode->content_type->label() }}
                    </div>
                    <p class="cs-help">Het contenttype staat vast nadat een concept is aangemaakt.</p>
                </div>
            @else
                <div>
                    <label for="content_type" class="cs-label">Contenttype <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="content_type" name="content_type" required class="cs-field {{ $errors->has('content_type') ? 'cs-field-error' : '' }}" @error('content_type') aria-invalid="true" aria-describedby="content-type-error" @enderror>
                        <option value="">Kies een type</option>
                        @foreach ($contentTypes as $contentType)
                            <option value="{{ $contentType->value }}" @selected(old('content_type', $selectedContentType?->value) === $contentType->value)>{{ $contentType->label() }}</option>
                        @endforeach
                    </select>
                    @error('content_type')<p id="content-type-error" class="cs-error">{{ $message }}</p>@enderror
                </div>
            @endif

            <div>
                <label for="locale" class="cs-label">Taalcode <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="locale" name="locale" type="text" required maxlength="10" value="{{ old('locale', $editing ? $contentNode->default_locale : ($selectedTemplate['locale'] ?? 'es-ES')) }}" placeholder="es-ES" class="cs-field {{ $errors->has('locale') ? 'cs-field-error' : '' }}" @error('locale') aria-invalid="true" aria-describedby="locale-error" @enderror>
                <p class="cs-help">Gebruik een taal- en regiocode, bijvoorbeeld <span lang="es">es-ES</span>.</p>
                @error('locale')<p id="locale-error" class="cs-error">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label for="slug" class="cs-label">Slug <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="slug" name="slug" type="text" required maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $editing ? $contentNode->slug : ($selectedTemplate['slug'] ?? '')) }}" class="cs-field {{ $errors->has('slug') ? 'cs-field-error' : '' }}" aria-describedby="slug-help @error('slug') slug-error @enderror" @error('slug') aria-invalid="true" @enderror>
                <p id="slug-help" class="cs-help">Alleen kleine letters, cijfers en koppeltekens, bijvoorbeeld <span lang="es">la-panaderia</span>.</p>
                @error('slug')<p id="slug-error" class="cs-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200" aria-labelledby="content-fields-title">
        <div class="cs-panel-header">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">
                    <x-content-studio.icon name="edit" />
                </span>
                <div>
                    <h2 id="content-fields-title" class="font-bold text-slate-900">Inhoud</h2>
                    <p class="mt-1 text-sm text-slate-500">Voeg de titel, korte context en volledige inhoud toe.</p>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-5 sm:p-6">
            <div>
                <label for="title" class="cs-label">Titel <span class="text-red-600" aria-hidden="true">*</span></label>
                <input id="title" name="title" type="text" required maxlength="255" value="{{ old('title', $localization?->title ?? ($selectedTemplate['title'] ?? '')) }}" class="cs-field {{ $errors->has('title') ? 'cs-field-error' : '' }}" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title')<p id="title-error" class="cs-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="summary" class="cs-label">Samenvatting <span class="font-normal text-slate-400">(optioneel)</span></label>
                <textarea id="summary" name="summary" rows="3" class="cs-field resize-y {{ $errors->has('summary') ? 'cs-field-error' : '' }}" @error('summary') aria-invalid="true" aria-describedby="summary-error" @enderror>{{ old('summary', $localization?->summary ?? ($selectedTemplate['summary'] ?? '')) }}</textarea>
                <p class="cs-help">Een korte redactionele omschrijving die helpt bij zoeken en beoordelen.</p>
                @error('summary')<p id="summary-error" class="cs-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="body" class="cs-label">Inhoud <span class="font-normal text-slate-400">(optioneel)</span></label>
                <textarea id="body" name="body" rows="8" class="cs-field min-h-40 resize-y leading-6 {{ $errors->has('body') ? 'cs-field-error' : '' }}" @error('body') aria-invalid="true" aria-describedby="body-error" @enderror>{{ old('body', $localization?->body ?? ($selectedTemplate['body'] ?? '')) }}</textarea>
                <p class="cs-help">Voer de volledige leerinhoud in. Typespecifieke editors volgen in een latere fase.</p>
                @error('body')<p id="body-error" class="cs-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200" aria-labelledby="domain-data-title">
        <div class="cs-panel-header">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">{ }</span>
                <div>
                    <h2 id="domain-data-title" class="font-bold text-slate-900">Speeldata</h2>
                    <p class="mt-1 text-sm text-slate-500">De versiegebonden JSON waarmee de wereld of dialoog in de frontend wordt opgebouwd.</p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <label for="domain_data" class="cs-label">JSON-contract <span class="font-normal text-slate-400">(optioneel voor niet-speelbare content)</span></label>
            <textarea
                id="domain_data"
                name="domain_data"
                rows="22"
                spellcheck="false"
                class="cs-field min-h-96 resize-y font-mono text-xs leading-5 {{ $errors->has('domain_data') ? 'cs-field-error' : '' }}"
                aria-describedby="domain-data-help @error('domain_data') domain-data-error @enderror"
                @error('domain_data') aria-invalid="true" @enderror
            >{{ old('domain_data', $domainDataJson) }}</textarea>
            <div id="domain-data-help" class="mt-3 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 sm:flex-row sm:items-start sm:justify-between">
                <p><strong>Veilige grens:</strong> JSON wordt vóór opslaan gecontroleerd. Bekende wereld- en gesprekscontracten krijgen extra structuurvalidatie.</p>
                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-amber-800">Alleen concept</span>
            </div>
            @error('domain_data')<p id="domain-data-error" class="cs-error">{{ $message }}</p>@enderror
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
        <a href="{{ $editing ? route('content-studio.content.show', $contentNode) : route('content-studio.content.index') }}" class="cs-button-secondary">Annuleren</a>
        <button type="submit" class="cs-button-primary">
            <x-content-studio.icon :name="$editing ? 'edit' : 'plus'" class="size-4" />
            {{ $editing ? 'Nieuwe revisie opslaan' : 'Concept aanmaken' }}
        </button>
    </div>
</div>
