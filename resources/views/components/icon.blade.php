@props(['name', 'size' => '1.15em', 'stroke' => 1.9])
@php
    // Clean 24×24 line icons (logo/slate style). Inherit color via currentColor; a few use a filled dot.
    $paths = [
        // state badges
        'waiting'  => '<circle cx="12" cy="12" r="8"/>',
        'open'     => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.2" fill="currentColor" stroke="none"/>',
        // working: «Matrix» digital rain — three columns of dashes flowing down (CSS .db-rain), green glow
        'working'  => '<rect x="3.5" y="3.5" width="17" height="17" rx="2"/><path class="db-rain" d="M8.5 6.5v11"/><path class="db-rain db-rain-2" d="M12 6.5v11"/><path class="db-rain db-rain-3" d="M15.5 6.5v11"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.3 9.2a2.7 2.7 0 1 1 3.8 2.5c-.9.4-1.1 1-1.1 1.8"/><circle cx="12" cy="16.6" r=".7" fill="currentColor" stroke="none"/>',
        'done'     => '<circle cx="12" cy="12" r="9"/><path d="M8 12.4l2.6 2.6 5.4-5.8"/>',
        // commands
        'resume'   => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 4v4h-4"/>',
        'archive'  => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><path d="M10 12h4"/>',
        'trash'    => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M10 11v6M14 11v6"/>',
        'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
        'edit'     => '<path d="M4 20h4l10-10a2 2 0 0 0-2.8-2.8L5 17l-1 3z"/><path d="M13.5 6.5l4 4"/>',
        'restore'  => '<path d="M9 5 4 10l5 5"/><path d="M4 10h9a7 7 0 0 1 0 14h-2"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'check-all'=> '<path d="M3 12.5l4 4L14 9"/><path d="M10 12.5l4 4L21 9"/>',
        'ban'      => '<circle cx="12" cy="12" r="8.5"/><path d="M6 6l12 12"/>',
        'chevron'  => '<path d="M6 9l6 6 6-6"/>',
        'grip'     => '<circle cx="9" cy="6" r="1.3" fill="currentColor" stroke="none"/><circle cx="15" cy="6" r="1.3" fill="currentColor" stroke="none"/><circle cx="9" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="9" cy="18" r="1.3" fill="currentColor" stroke="none"/><circle cx="15" cy="18" r="1.3" fill="currentColor" stroke="none"/>',
        'book'     => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21z"/><path d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20"/>',
        'move'     => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1"/><path d="M3 7v11a2 2 0 0 0 2 2h6"/><path d="M14 16h7M18 13l3 3-3 3"/>',
        'mic'      => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0"/><path d="M12 17.5V21M9 21h6"/>',
        'coins'    => '<ellipse cx="12" cy="6.5" rx="7" ry="3"/><path d="M5 6.5v5c0 1.7 3.1 3 7 3s7-1.3 7-3v-5"/><path d="M5 11.5v5c0 1.7 3.1 3 7 3s7-1.3 7-3v-5"/>',
    ];
    $inner = $paths[$name] ?? '';
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}"
     fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['class' => 'inline-block shrink-0 align-[-0.15em]']) }} aria-hidden="true" focusable="false">
    {!! $inner !!}
</svg>
