@extends('layouts.content-studio')

@section('title', 'Nieuwe release')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('content-studio.releases.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-brand-700">
            <x-content-studio.icon name="arrow-left" class="size-4" />
            Terug naar releases
        </a>
        <p class="cs-eyebrow mt-6">Nieuwe publicatiebundel</p>
        <h1 class="cs-page-title">Conceptrelease maken</h1>
        <p class="cs-page-description">De release start leeg en is pas uitvoerbaar nadat goedgekeurde revisies zijn toegevoegd en alle preflightcontroles slagen.</p>

        <form method="POST" action="{{ route('content-studio.releases.store') }}" class="cs-panel mt-8 overflow-hidden">
            @csrf
            <div class="cs-panel-header">
                <h2 class="font-bold text-slate-900">Releasegegevens</h2>
                <p class="mt-1 text-sm text-slate-500">Kanaal en gewenst moment kunnen na aanmaken niet stil worden omzeild.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="name" class="cs-label">Naam</label>
                    <input id="name" name="name" type="text" required maxlength="180" value="{{ old('name') }}" class="cs-field" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<p id="name-error" class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="cs-label">Omschrijving <span class="font-normal text-slate-400">(optioneel)</span></label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" class="cs-field" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                    @error('description')<p id="description-error" class="cs-error">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="target_channel" class="cs-label">Doelkanaal</label>
                        <select id="target_channel" name="target_channel" required class="cs-field">
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->value }}" @selected(old('target_channel', App\Enums\ContentReleaseChannel::Preview->value) === $channel->value)>{{ $channel->label() }}</option>
                            @endforeach
                        </select>
                        @error('target_channel')<p class="cs-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="desired_publish_at" class="cs-label">Gewenst moment <span class="font-normal text-slate-400">(optioneel)</span></label>
                        <input id="desired_publish_at" name="desired_publish_at" type="datetime-local" value="{{ old('desired_publish_at') }}" class="cs-field">
                        @error('desired_publish_at')<p class="cs-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <a href="{{ route('content-studio.releases.index') }}" class="cs-button-secondary">Annuleren</a>
                <button type="submit" class="cs-button-primary"><x-content-studio.icon name="plus" class="size-4" />Release maken</button>
            </div>
        </form>
    </div>
@endsection
