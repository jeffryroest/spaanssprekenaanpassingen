@extends('layouts.content-studio')

@section('title', 'Nieuw concept')

@section('content')
    <div class="max-w-4xl">
        <a href="{{ route('content-studio.content.index') }}" class="text-sm font-medium text-orange-300 hover:text-orange-200">← Terug naar de catalogus</a>
        <p class="mt-6 text-sm font-semibold uppercase tracking-[0.18em] text-orange-300">Veilige creatie</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-white">Nieuw concept</h1>
        <p class="mt-3 text-stone-300">De content wordt altijd als concept opgeslagen en krijgt automatisch een eerste onveranderlijke revisie.</p>

        <form method="POST" action="{{ route('content-studio.content.store') }}" class="mt-8">
            @csrf
            @include('content-studio.content._form')
        </form>
    </div>
@endsection
