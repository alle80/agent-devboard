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
        // settings page
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'bot'      => '<rect x="4" y="8" width="16" height="12" rx="2"/><path d="M12 4v4M8 4h8"/><circle cx="9" cy="14" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="14" r="1.2" fill="currentColor" stroke="none"/><path d="M9.5 17.5h5"/>',
        'bolt'     => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'board'    => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'bell'     => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'bell-off' => '<path d="M6 16V11a6 6 0 0 1 9.3-5"/><path d="M18 11v5l1.5 2H6"/><path d="M10 20a2 2 0 0 0 4 0"/><path d="M4 4l16 16"/>',
        'palette'  => '<path d="M12 3a9 9 0 1 0 0 18h1.5a2 2 0 0 0 0-4H13a1.5 1.5 0 0 1 0-3h3a5 5 0 0 0 0-10z"/><circle cx="7.5" cy="11" r="1.2" fill="currentColor" stroke="none"/><circle cx="10" cy="7" r="1.2" fill="currentColor" stroke="none"/><circle cx="14.5" cy="7" r="1.2" fill="currentColor" stroke="none"/>',
        'alert'    => '<path d="M12 3 2.5 20h19z"/><path d="M12 9.5v5"/><circle cx="12" cy="17.3" r=".8" fill="currentColor" stroke="none"/>',
        'send'     => '<path d="M21 3 10 14"/><path d="M21 3 14 21l-4-7-7-4z"/>',
        'package'  => '<path d="M12 3 4 7v10l8 4 8-4V7z"/><path d="M4 7l8 4 8-4M12 11v10"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'logout'   => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h7a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-7"/>',
        'ruler'    => '<path d="M3 17 17 3l4 4L7 21z"/><path d="M7 13l2 2M10 10l2 2M13 7l2 2"/>',
        'list'     => '<path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1" fill="currentColor" stroke="none"/>',
        'image'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-8 8"/>',
        'camera'   => '<path d="M4 8h3l2-2h6l2 2h3v11H4z"/><circle cx="12" cy="13" r="3.2"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'puzzle'   => '<path d="M10 4a2 2 0 1 1 4 0v2h4v4a2 2 0 1 0 0 4v4h-4v-2a2 2 0 1 0-4 0v2H6v-4a2 2 0 1 1 0-4V6h4z"/>',
        'link'     => '<path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'tasks'    => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 12l2.5 2.5L16 9"/>',
        'play'     => '<path d="M7 4.5v15l12-7.5z"/>',
        'pause'    => '<path d="M8 5v14M16 5v14"/>',
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
