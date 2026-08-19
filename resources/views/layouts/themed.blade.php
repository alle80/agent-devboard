@php($theme = $theme ?? request()->route('theme'))
@php($theme = ($theme && \Alle80\Devboard\Themes::has($theme)) ? $theme : \Alle80\Devboard\Http\Middleware\RememberStyle::current())
@php($theme = \Alle80\Devboard\Themes::has($theme) ? $theme : \Alle80\Devboard\Themes::default())
@php($t = \Alle80\Devboard\Themes::get($theme))
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111111">
    <title>{{ $title ?? 'Agent Devboard — '.$t['label'] }}</title>
    @if (! empty($t['icon_img']))
        <link rel="icon" href="{{ asset($t['icon_img']) }}">
    @else
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>{{ $t['icon'] }}</text></svg>">
    @endif
    @if (! empty($t['fonts']) && config('devboard.fonts_url'))
        <link rel="preconnect" href="{{ parse_url(config('devboard.fonts_url'), PHP_URL_SCHEME) }}://{{ parse_url(config('devboard.fonts_url'), PHP_URL_HOST) }}">
        <link href="{{ config('devboard.fonts_url') }}{{ $t['fonts'] }}" rel="stylesheet">
    @endif
    <x-devboard::assets />
    @if (! empty($t['css_url']))
        <link rel="stylesheet" href="{{ $t['css_url'] }}">
    @endif
</head>
<body class="tl-body theme-{{ $theme }} min-h-screen antialiased">

    {{-- Decorazioni sparse del tema --}}
    @if (! empty($t['deco']))
        <div class="pointer-events-none fixed inset-0 select-none" aria-hidden="true">
            @php($spots = [
                'top-24 left-[4%] rotate-12',
                'top-1/3 right-[4%] -rotate-6',
                'bottom-24 left-[6%] -rotate-12',
                'bottom-16 right-[6%] rotate-6',
            ])
            @foreach (array_slice($t['deco'], 0, 4) as $i => $emoji)
                <span class="absolute {{ $spots[$i] }} hidden text-3xl opacity-40 lg:block">{{ $emoji }}</span>
            @endforeach
        </div>
    @endif

    <x-devboard::style-switcher :current="$theme" />

    <livewire:devboard::checklist-switcher />

    <x-devboard::toasts />

    @if (\Alle80\Devboard\Mode::isLocal())
        {{-- Local mode: no authentication — say it loudly on every page --}}
        <div class="db-local-banner fixed right-3 bottom-3 z-[70] max-w-xs rounded-lg border-2 border-black bg-amber-200 px-3 py-2 text-xs font-bold text-black shadow-[2px_2px_0_#000]" role="status" style="font-family: system-ui, sans-serif">
            {{ __('devboard::t.local_banner') }}
        </div>
    @endif

    <div class="relative">
        {{ $slot }}
    </div>

    <x-devboard::board-tab />
</body>
</html>
