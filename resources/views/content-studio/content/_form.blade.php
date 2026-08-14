@php
    $editing = isset($contentNode);
    $localization = $editing ? $contentNode->defaultLocalization() : null;
@endphp

@if ($errors->any())
    <div class="rounded-xl border border-red-300/20 bg-red-300/10 p-5 text-sm text-red-100" role="alert">
        <p class="font-semibold">Controleer de gemarkeerde velden.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-6 grid gap-6 rounded-2xl border border-white/10 bg-white/[0.04] p-6 md:grid-cols-2">
    @if ($editing)
        <input type="hidden" name="expected_version" value="{{ $contentNode->current_version }}">
        <div class="md:col-span-2 rounded-xl border border-white/10 bg-stone-900/70 px-4 py-3">
            <p class="text-xs uppercase tracking-wider text-stone-500">Contenttype</p>
            <p class="mt-1 font-medium text-white">{{ $contentNode->content_type->label() }}</p>
        </div>
    @else
        <div>
            <label for="content_type" class="block text-sm font-medium text-stone-200">Contenttype</label>
            <select id="content_type" name="content_type" required class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('content_type') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">
                <option value="">Kies een type</option>
                @foreach ($contentTypes as $contentType)
                    <option value="{{ $contentType->value }}" @selected(old('content_type') === $contentType->value)>{{ $contentType->label() }}</option>
                @endforeach
            </select>
            @error('content_type')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label for="slug" class="block text-sm font-medium text-stone-200">Slug</label>
        <input id="slug" name="slug" type="text" required maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $editing ? $contentNode->slug : '') }}" aria-describedby="slug-help" class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('slug') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">
        <p id="slug-help" class="mt-2 text-xs text-stone-500">Alleen kleine letters, cijfers en koppeltekens, bijvoorbeeld <span lang="es">la-panaderia</span>.</p>
        @error('slug')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="locale" class="block text-sm font-medium text-stone-200">Taalcode</label>
        <input id="locale" name="locale" type="text" required maxlength="10" value="{{ old('locale', $editing ? $contentNode->default_locale : 'es-ES') }}" placeholder="es-ES" class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('locale') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">
        @error('locale')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label for="title" class="block text-sm font-medium text-stone-200">Titel</label>
        <input id="title" name="title" type="text" required maxlength="255" value="{{ old('title', $localization?->title) }}" class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('title') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">
        @error('title')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label for="summary" class="block text-sm font-medium text-stone-200">Samenvatting <span class="text-stone-500">(optioneel)</span></label>
        <textarea id="summary" name="summary" rows="3" class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('summary') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">{{ old('summary', $localization?->summary) }}</textarea>
        @error('summary')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label for="body" class="block text-sm font-medium text-stone-200">Inhoud <span class="text-stone-500">(optioneel)</span></label>
        <textarea id="body" name="body" rows="10" class="mt-2 w-full rounded-xl border bg-stone-900 px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-orange-400/30 {{ $errors->has('body') ? 'border-red-400' : 'border-white/10 focus:border-orange-400' }}">{{ old('body', $localization?->body) }}</textarea>
        @error('body')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="rounded-xl bg-orange-400 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2 focus:ring-offset-stone-950">
        {{ $editing ? 'Nieuwe revisie opslaan' : 'Concept aanmaken' }}
    </button>
    <a href="{{ $editing ? route('content-studio.content.show', $contentNode) : route('content-studio.content.index') }}" class="rounded-xl border border-white/10 px-5 py-3 text-sm font-medium text-stone-300 hover:bg-white/[0.06] focus:outline-none focus:ring-2 focus:ring-orange-400">Annuleren</a>
</div>
