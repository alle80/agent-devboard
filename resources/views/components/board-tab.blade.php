@php
    $side = 'right';
    try {
        $side = app(\Alle80\Devboard\Settings\AppSettings::class)->tab_side ?: 'right';
    } catch (\Throwable $e) {
        // settings not migrated yet — fall back
    }
    $dash = config('devboard.dashboard_route');
    $onDashboard = $dash && request()->path() === ltrim($dash, '/');
@endphp

@if ($dash && ! $onDashboard)
    {{-- Slide-out dashboard tab (desktop only), Laravel-debugbar style: a handle pinned to one
         edge; click opens a resizable panel with the dashboard in an iframe. --}}
    <div
        x-data="{
            open: false,
            width: 460,
            side: @js($side),
            init() {
                this.open = localStorage.getItem('db_tab_open') === '1';
                const w = parseInt(localStorage.getItem('db_tab_w'), 10);
                if (!isNaN(w)) this.width = w;
            },
            toggle() {
                this.open = !this.open;
                localStorage.setItem('db_tab_open', this.open ? '1' : '0');
            },
            startResize(e) {
                e.preventDefault();
                const startX = e.clientX, startW = this.width, side = this.side;
                const move = (ev) => {
                    const dx = ev.clientX - startX;
                    let w = side === 'right' ? startW - dx : startW + dx;
                    this.width = Math.max(300, Math.min(Math.round(window.innerWidth * 0.7), w));
                };
                const up = () => {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                    document.body.style.userSelect = '';
                    localStorage.setItem('db_tab_w', this.width);
                };
                document.body.style.userSelect = 'none';
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
            },
        }"
        x-cloak
        class="db-tab db-tab-{{ $side }} hidden lg:block"
        :style="open ? ('--db-w:' + width + 'px') : ''"
    >
        {{-- Handle (visible when closed) --}}
        <button type="button" class="db-tab-handle" @click="toggle()" x-show="!open" :aria-expanded="open"
                aria-label="{{ __('devboard::t.dashboard_tab') }}">
            <span class="db-tab-handle-txt">{{ __('devboard::t.dashboard_tab') }}</span>
        </button>

        {{-- Panel --}}
        <aside class="db-tab-panel" x-show="open"
               x-transition:enter="db-anim" x-transition:enter-start="db-off" x-transition:enter-end="db-on"
               x-transition:leave="db-anim" x-transition:leave-start="db-on" x-transition:leave-end="db-off"
               aria-label="{{ __('devboard::t.dashboard_tab') }}">
            <div class="db-resize" @mousedown="startResize($event)" title="↔"></div>
            <header class="db-tab-head">
                <span class="db-tab-title">{{ __('devboard::t.dashboard_tab') }}</span>
                <a href="{{ $dash }}" class="db-tab-open" title="{{ __('devboard::t.dashboard_open_full') }}" target="_top">⤢</a>
                <button type="button" class="db-tab-close" @click="toggle()" aria-label="{{ __('devboard::t.close') }}">✕</button>
            </header>
            <iframe class="db-tab-frame" :src="open ? @js($dash) : 'about:blank'" loading="lazy"
                    title="{{ __('devboard::t.dashboard_tab') }}"></iframe>
        </aside>
    </div>
@endif
