@php($theme = $theme ?? request()->route('theme'))
@php($theme = ($theme && \Alle80\Griglia\Themes::has($theme)) ? $theme : \Alle80\Griglia\Http\Middleware\RememberStyle::current())
@php($theme = \Alle80\Griglia\Themes::has($theme) ? $theme : \Alle80\Griglia\Themes::default())
@php($t = \Alle80\Griglia\Themes::get($theme))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    {{-- interactive-widget: la tastiera virtuale RIDIMENSIONA il viewport (100dvh si accorcia) invece di
         coprire il contenuto — senza, su Android l'editor dei sotto-task finisce sotto la tastiera (task 303). --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111111">
    <title>{{ $title ?? 'Griglia — '.$t['label'] }}</title>
    {{-- La favicon è sempre il marchio Griglia, qualunque sia il tema: la linguetta identifica l'app, non
         lo skin (l'icona del tema resta nel menu degli stili). Il ?v= è la data del file: senza, i browser
         tengono la vecchia icona per giorni anche dopo un ricaricamento forzato. --}}
    @php($iconV = @filemtime(public_path('vendor/griglia/images/brand/mark.svg')) ?: '1')
    <link rel="icon" href="{{ asset('vendor/griglia/images/brand/mark.svg') }}?v={{ $iconV }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('vendor/griglia/images/brand/mark-32.png') }}?v={{ $iconV }}" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('vendor/griglia/images/brand/mark-180.png') }}?v={{ $iconV }}">
    @if (! empty($t['fonts']) && config('griglia.fonts_url'))
        <link rel="preconnect" href="{{ parse_url(config('griglia.fonts_url'), PHP_URL_SCHEME) }}://{{ parse_url(config('griglia.fonts_url'), PHP_URL_HOST) }}">
        <link href="{{ config('griglia.fonts_url') }}{{ $t['fonts'] }}" rel="stylesheet">
    @endif
    <x-griglia::assets />
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

    <livewire:griglia::checklist-switcher />

    @unless (\Alle80\Griglia\Mode::isLocal())
        <div class="tl-chrome fixed top-3 right-3 z-[60]">
            <livewire:griglia::notification-bell />
        </div>
    @endunless

    <x-griglia::toasts />

    @if (\Alle80\Griglia\Mode::isLocal())
        {{-- Local mode: no authentication — say it loudly on every page --}}
        <div class="db-local-banner fixed right-3 bottom-3 z-[70] max-w-xs rounded-lg border-2 border-black bg-amber-200 px-3 py-2 text-xs font-bold text-black shadow-[2px_2px_0_#000]" role="status" style="font-family: system-ui, sans-serif">
            {{ __('griglia::t.local_banner') }}
        </div>
    @endif

    <div class="relative">
        {{ $slot }}
    </div>

    <x-griglia::board-tab />
</body>
</html>
