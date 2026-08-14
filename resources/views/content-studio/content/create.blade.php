@extends('layouts.content-studio')

@section('title', 'Nieuw concept')

@section('content')
    <div class="max-w-5xl">
        <a href="{{ route('content-studio.content.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-700">
            <x-content-studio.icon name="arrow-left" class="size-4" />
            Terug naar de catalogus
        </a>

        <div class="mt-6">
            <p class="cs-eyebrow">Veilige creatie</p>
            <h1 class="cs-page-title">Nieuw concept</h1>
            <p class="cs-page-description">Nieuwe content start altijd als concept en krijgt automatisch een eerste, onveranderlijke revisie.</p>
        </div>

        <form method="POST" action="{{ route('content-studio.content.store') }}" class="mt-8">
            @csrf
            @include('content-studio.content._form')
        </form>
    </div>
@endsection
