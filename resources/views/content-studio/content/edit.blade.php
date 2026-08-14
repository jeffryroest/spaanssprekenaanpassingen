@extends('layouts.content-studio')

@section('title', 'Concept bewerken')

@section('content')
    <div class="max-w-5xl">
        <a href="{{ route('content-studio.content.show', $contentNode) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-700">
            <x-content-studio.icon name="arrow-left" class="size-4" />
            Terug naar het contentobject
        </a>

        <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="cs-eyebrow">Concept · versie {{ $contentNode->current_version }}</p>
                <h1 class="cs-page-title">Concept bewerken</h1>
                <p class="cs-page-description">Opslaan overschrijft niets, maar maakt een nieuwe conceptrevisie voor een volledig controleerbare geschiedenis.</p>
            </div>
            <x-content-studio.status-badge :status="$contentNode->status" class="mt-1" />
        </div>

        <form method="POST" action="{{ route('content-studio.content.update', $contentNode) }}" class="mt-8">
            @csrf
            @method('PUT')
            @include('content-studio.content._form')
        </form>
    </div>
@endsection
