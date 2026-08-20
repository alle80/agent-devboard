@props(['current' => null])

{{-- Menu di cambio stile, identico su tutte le pagine --}}
<details class="fixed top-3 right-3 z-[60]" style="font-family: system-ui, sans-serif">
    <summary class="cursor-pointer list-none rounded-lg border-2 border-black bg-white px-2.5 py-1 text-xs font-bold sm:px-3 sm:py-1.5 sm:text-sm text-black shadow-[2px_2px_0_#000] select-none hover:bg-amber-100 active:translate-y-px [&::-webkit-details-marker]:hidden">
        <x-griglia::icon name="palette" /> {{ __('griglia::t.style') }} <x-griglia::icon name="chevron" size=".9em" class="opacity-60" />
    </summary>
    <div class="absolute right-0 mt-1.5 max-h-[75vh] w-56 overflow-y-auto rounded-lg border-2 border-black bg-white p-1 text-black shadow-[3px_3px_0_#000]">
        @foreach (\Alle80\Griglia\Themes::switcher() as $slug => $s)
            <a
                href="{{ $s['url'] }}"
                class="block rounded px-2 py-1.5 text-sm font-bold hover:bg-amber-100 {{ $current === $slug ? 'bg-amber-200' : '' }}"
            ><x-griglia::theme-icon :theme="$s" /> {{ $s['label'] }}@if ($current === $slug) <x-griglia::icon name="check" size=".9em" />@endif</a>
        @endforeach
    </div>
</details>
