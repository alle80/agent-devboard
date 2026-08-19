@props(['name', 'size' => '1.15em', 'stroke' => 1.9])
@php
    // Clean 24×24 line icons (logo/slate style). Inherit color via currentColor; a few use a filled dot.
    $paths = [
        // state badges
        'waiting'  => '<circle cx="12" cy="12" r="8"/>',
        'open'     => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.2" fill="currentColor" stroke="none"/>',
        'working'  => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.5 5.5l2.1 2.1M16.4 16.4l2.1 2.1M18.5 5.5l-2.1 2.1M7.6 16.4l-2.1 2.1"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.3 9.2a2.7 2.7 0 1 1 3.8 2.5c-.9.4-1.1 1-1.1 1.8"/><circle cx="12" cy="16.6" r=".7" fill="currentColor" stroke="none"/>',
        'done'     => '<circle cx="12" cy="12" r="9"/><path d="M8 12.4l2.6 2.6 5.4-5.8"/>',
        // commands
        'resume'   => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v4h-4"/>',
        'archive'  => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><path d="M10 12h4"/>',
        'trash'    => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M10 11v6M14 11v6"/>',
        'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
    $inner = $paths[$name] ?? '';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}"
     fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['class' => 'inline-block shrink-0 align-[-0.15em]']) }} aria-hidden="true" focusable="false">
    {!! $inner !!}
</svg>
