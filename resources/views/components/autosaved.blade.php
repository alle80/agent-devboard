{{--
    Spia «salvato»: compare per un attimo a ogni salvataggio automatico (evento Livewire
    «griglia-autosaved» del componente) e sparisce da sola. Niente toast: ne uscirebbe uno a
    ogni pausa nella digitazione.
--}}
<span
    x-data="{ shown: false, timer: null }"
    x-on:griglia-autosaved.window="shown = true; clearTimeout(timer); timer = setTimeout(() => shown = false, 2000)"
    x-show="shown"
    x-transition.opacity.duration.400ms
    x-cloak
    aria-live="polite"
    {{ $attributes->merge(['class' => 'shrink-0 inline-flex items-center gap-1 text-xs font-normal opacity-70']) }}
    style="font-family: system-ui, sans-serif"
><x-griglia::icon name="check" /> {{ __('griglia::t.autosaved') }}</span>
