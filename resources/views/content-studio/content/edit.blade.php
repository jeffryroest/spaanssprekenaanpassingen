@extends('layouts.content-studio')

@section('title', 'Concept bewerken')

@section('content')
    <div class="max-w-4xl">
        <a href="{{ route('content-studio.content.show', $contentNode) }}" class="text-sm font-medium text-orange-300 hover:text-orange-200">← Terug naar het contentobject</a>
        <p class="mt-6 text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Versie {{ $contentNode->current_version }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">Concept bewerken</h1>
        <p class="mt-3 text-stone-300">Opslaan wijzigt geen bestaande revisie, maar maakt een nieuwe conceptrevisie.</p>

        <form method="POST" action="{{ route('content-studio.content.update', $contentNode) }}" class="mt-8">
            @csrf
            @method('PUT')
            @include('content-studio.content._form')
        </form>
    </div>
@endsection
