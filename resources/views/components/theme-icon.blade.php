@props(['theme', 'size' => '1em', 'class' => ''])

{{-- Icona di un tema: immagine se il tema ne ha una (es. Tux), altrimenti l'emoji --}}
@if (! empty($theme['icon_img']))
    <img
        src="{{ asset($theme['icon_img']) }}"
        alt="{{ $theme['label'] ?? '' }}"
        width="64"
        height="64"
        style="height: {{ $size }}; width: auto;"
        class="inline-block align-[-0.15em] {{ $class }}"
        draggable="false"
    >
@else
    <span class="{{ $class }}">{{ $theme['icon'] ?? '' }}</span>
@endif
