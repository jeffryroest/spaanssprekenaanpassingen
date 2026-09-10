@extends('layouts.player-auth')

@section('title', 'Aanmelden')
@section('description', 'Maak gratis een account en bewaar je voortgang in de interactieve Spaanse webgame.')

@section('content')
    <p class="player-auth-kicker">Tu aventura empieza aquí</p>
    <h2 id="auth-title" class="player-auth-title">Maak je spelersaccount</h2>
    <p class="player-auth-description">Je account bewaart alleen de voortgang en beloningen die nodig zijn om je spel later te hervatten.</p>

    @if ($errors->any())
        <div class="player-auth-error" role="alert">
            <p class="font-black">Aanmelden is nog niet gelukt.</p>
            <p class="mt-1">Controleer de gemarkeerde velden.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5" novalidate>
        @csrf

        <div>
            <label for="name" class="player-auth-label">Naam</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="120" required autofocus class="player-auth-field {{ $errors->has('name') ? 'player-auth-field-error' : '' }}" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')<p id="name-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="player-auth-label">E-mailadres</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required class="player-auth-field {{ $errors->has('email') ? 'player-auth-field-error' : '' }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="player-auth-label">Wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="player-auth-field {{ $errors->has('password') ? 'player-auth-field-error' : '' }}" aria-describedby="password-help @error('password') password-error @enderror">
            <p id="password-help" class="mt-2 text-xs leading-5 text-[#7a6b62]">Minimaal 12 tekens, met letters en cijfers.</p>
            @error('password')<p id="password-error" class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="player-auth-label">Herhaal wachtwoord</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="player-auth-field">
        </div>

        <button type="submit" class="player-auth-primary">Account maken</button>
    </form>

    <div class="mt-6 rounded-2xl border border-[#315d47]/15 bg-[#edf4e9] p-4 text-xs leading-5 text-[#53675b]">
        Een account maken start geen betaling. Je kunt eerst gratis kennismaken en daarna zelf je proefweek zonder betaalgegevens activeren.
    </div>

    <p class="mt-7 text-center text-sm text-[#6f6158]">
        Heb je al een account?
        <a href="{{ route('login') }}" class="font-black text-[#a9472b] underline decoration-[#a9472b]/30 underline-offset-4">Log hier in</a>
    </p>
@endsection
