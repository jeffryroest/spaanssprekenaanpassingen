@props(['href' => route('home')])

<a href="{{ $href }}" {{ $attributes->class(['player-brand']) }} aria-label="Spaansspreken.nl startpagina">
    <span class="player-brand-mark" aria-hidden="true">S</span>
    <strong>Spaansspreken<span>.nl</span></strong>
</a>
