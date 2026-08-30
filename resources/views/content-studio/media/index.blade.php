@extends('layouts.content-studio')

@section('title', 'Mediabibliotheek')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="cs-eyebrow">Redactionele media</p>
            <h1 class="cs-page-title">Mediabibliotheek</h1>
            <p class="cs-page-description">Upload uitsluitend scènebeelden en audio waarvoor herkomst, rechten en toegankelijke tekst zijn vastgelegd. Spelersopnamen komen nooit in deze bibliotheek.</p>
        </div>
        <span class="status-chip">{{ $mediaAssets->total() }} media</span>
    </div>

    @can('content-studio.edit')
        <details class="cs-panel mt-8 overflow-hidden" @if($errors->any()) open @endif>
            <summary class="cursor-pointer list-none px-5 py-4 font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:px-6">
                Nieuw medium veilig uploaden
            </summary>
            <form method="POST" action="{{ route('content-studio.media.store') }}" enctype="multipart/form-data" class="grid gap-6 border-t border-slate-200 p-5 md:grid-cols-2 sm:p-6">
                @csrf
                <div>
                    <label for="media-file" class="cs-label">Bestand <span class="text-red-600">*</span></label>
                    <input id="media-file" name="file" type="file" required accept="image/jpeg,image/png,image/webp,audio/mpeg,audio/ogg,audio/webm,audio/wav" class="cs-field file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700">
                    <p class="cs-help">JPG, PNG, WebP, MP3, OGG, WebM of WAV · maximaal 20 MB.</p>
                    @error('file')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-kind" class="cs-label">Mediatype <span class="text-red-600">*</span></label>
                    <select id="media-kind" name="kind" required class="cs-field">
                        @foreach($mediaKinds as $kind)
                            <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value)>{{ $kind->label() }}</option>
                        @endforeach
                    </select>
                    @error('kind')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="media-title" class="cs-label">Interne titel <span class="text-red-600">*</span></label>
                    <input id="media-title" name="title" required maxlength="255" value="{{ old('title') }}" class="cs-field">
                    @error('title')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="media-description" class="cs-label">Beschrijving</label>
                    <textarea id="media-description" name="description" rows="3" maxlength="2000" class="cs-field">{{ old('description') }}</textarea>
                    @error('description')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-alt" class="cs-label">Alt-tekst voor afbeelding</label>
                    <textarea id="media-alt" name="alt_text" rows="4" maxlength="1000" class="cs-field">{{ old('alt_text') }}</textarea>
                    <p class="cs-help">Verplicht bij een afbeelding; beschrijf functie en relevante visuele informatie.</p>
                    @error('alt_text')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-transcript" class="cs-label">Transcript voor audio</label>
                    <textarea id="media-transcript" name="transcript" rows="4" maxlength="20000" class="cs-field">{{ old('transcript') }}</textarea>
                    <p class="cs-help">Verplicht bij audio; vermeld ook relevante niet-gesproken geluiden.</p>
                    @error('transcript')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-rights" class="cs-label">Rechtenstatus <span class="text-red-600">*</span></label>
                    <select id="media-rights" name="rights_status" required class="cs-field">
                        @foreach($rightsStatuses as $status)
                            <option value="{{ $status->value }}" @selected(old('rights_status', App\Enums\MediaRightsStatus::Owned->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('rights_status')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-expiry" class="cs-label">Rechten geldig tot</label>
                    <input id="media-expiry" name="rights_expires_at" type="date" value="{{ old('rights_expires_at') }}" class="cs-field">
                    @error('rights_expires_at')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-source" class="cs-label">Bron</label>
                    <input id="media-source" name="source_name" maxlength="500" value="{{ old('source_name') }}" class="cs-field">
                    @error('source_name')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-creator" class="cs-label">Maker/rechthebbende</label>
                    <input id="media-creator" name="creator_name" maxlength="255" value="{{ old('creator_name') }}" class="cs-field">
                    @error('creator_name')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="media-license" class="cs-label">Licentie</label>
                    <input id="media-license" name="license_name" maxlength="255" value="{{ old('license_name') }}" class="cs-field">
                    @error('license_name')<p class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end justify-end">
                    <button class="cs-button-primary" type="submit">
                        <x-content-studio.icon name="media" class="size-4" />
                        Medium opslaan
                    </button>
                </div>
            </form>
        </details>
    @endcan

    <section class="mt-8" aria-labelledby="media-list-title">
        <h2 id="media-list-title" class="sr-only">Opgeslagen media</h2>
        @if($mediaAssets->isEmpty())
            <div class="cs-panel p-8 text-center">
                <p class="font-bold text-slate-900">Nog geen redactionele media</p>
                <p class="mt-2 text-sm text-slate-500">Upload een scènebeeld of omgevingsgeluid om het aan een nieuwe contentrevisie te koppelen.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($mediaAssets as $asset)
                    <article class="cs-panel overflow-hidden">
                        <div class="grid aspect-[16/9] place-items-center bg-slate-100">
                            @if($asset->kind === App\Enums\MediaKind::Image)
                                <img src="{{ route('content-studio.media.stream', $asset) }}" alt="{{ $asset->alt_text }}" class="size-full object-cover">
                            @else
                                <audio controls preload="none" class="w-[90%]">
                                    <source src="{{ route('content-studio.media.stream', $asset) }}" type="{{ $asset->mime_type }}">
                                </audio>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="status-chip">{{ $asset->kind->label() }}</span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $asset->isPublishable() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                                    {{ $asset->isPublishable() ? 'Publiceerbaar' : 'Geblokkeerd' }}
                                </span>
                            </div>
                            <h3 class="mt-4 font-bold text-slate-900">{{ $asset->title }}</h3>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $asset->original_name }} · {{ number_format($asset->byte_size / 1024, 0, ',', '.') }} kB</p>
                            <dl class="mt-4 grid gap-2 text-xs">
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Rechten</dt><dd class="font-semibold text-slate-700">{{ $asset->rights_status->label() }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Toegankelijkheid</dt><dd class="font-semibold {{ $asset->hasAccessibilityText() ? 'text-emerald-700' : 'text-red-700' }}">{{ $asset->hasAccessibilityText() ? 'Compleet' : 'Ontbreekt' }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Door</dt><dd class="truncate font-semibold text-slate-700">{{ $asset->creator?->name ?? 'Onbekend' }}</dd></div>
                            </dl>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $mediaAssets->links() }}</div>
        @endif
    </section>
@endsection
