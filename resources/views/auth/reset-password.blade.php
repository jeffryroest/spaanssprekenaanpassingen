@extends('layouts.player-auth')

@section('title', 'Nieuw wachtwoord')
@section('description', 'Stel een nieuw wachtwoord in voor je Spaansspreken.nl-account.')

@section('content')
    <p class="player-auth-kicker">Cuenta segura</p>
    <h2 id="auth-title" class="player-auth-title">Kies een nieuw wachtwoord</h2>
    <p class="player-auth-description">Na het wijzigen kun je weer veilig verder met je missies en beloningen.</p>

    @if ($errors->any())
        <div class="player-auth-error" role="alert">De link of invoer is niet geldig. Controleer de velden of vraag een nieuwe herstellink aan.</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="player-auth-label">E-mailadres</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus class="player-auth-field {{ $errors->has('email') ? 'player-auth-field-error' : '' }}">
            @error('email')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="player-auth-label">Nieuw wachtwoord</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="player-auth-field {{ $errors->has('password') ? 'player-auth-field-error' : '' }}">
            @error('password')<p class="player-auth-field-message" role="alert">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="player-auth-label">Herhaal nieuw wachtwoord</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="player-auth-field">
        </div>
        <button type="submit" class="player-auth-primary">Wachtwoord opslaan</button>
    </form>
@endsection
