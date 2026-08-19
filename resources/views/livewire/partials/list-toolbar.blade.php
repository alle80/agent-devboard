{{--
    Barra sopra la lista: ricerca a testo libero, filtri di stato, archivio.
    Variabili attese: $archivedCount, $filtering, $showArchived (dal componente), più le classi:
      $wrapClass   contenitore della barra
      $inputClass  campo di ricerca
      $chipClass   chip filtro (stato normale)
      $chipOnClass chip filtro attivo
      $btnClass    bottone archivio
--}}
<div class="{{ $wrapClass }} list-toolbar mb-4 space-y-2">
    {{-- Ricerca --}}
    <div class="relative">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('devboard::t.search_placeholder') }}"
            autocomplete="off"
            aria-label="{{ __('devboard::t.search_label') }}"
            class="{{ $inputClass }} w-full pr-9 pl-3"
        >
        @if (trim($search) !== '')
            <button
                type="button"
                wire:click="clearSearch"
                title="{{ __('devboard::t.clear_search') }}"
                aria-label="{{ __('devboard::t.clear_search') }}"
                class="absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer text-lg opacity-50 hover:opacity-100"
            ><x-devboard::icon name="close" /></button>
        @endif
    </div>

    {{-- Filtri di stato + archivio --}}
    <div class="flex flex-wrap items-center gap-1.5">
        @php($icons = ['todo' => 'waiting', 'done' => 'done', 'otw' => 'open', 'working' => 'working', 'question' => 'question'])
        @foreach (\Alle80\Devboard\Livewire\TodoList::filters() as $key => $label)
            <button
                type="button"
                wire:click="setFilter('{{ $key }}')"
                class="{{ $filter === $key ? $chipOnClass : $chipClass }} inline-flex cursor-pointer items-center gap-1 px-2.5 py-1 text-xs leading-none"
                aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
            >@isset($icons[$key])<span class="db-badge db-badge-{{ $icons[$key] }}"><x-devboard::icon :name="$icons[$key]" size="1.1em" :stroke="2" /></span>@endisset{{ $label }}</button>
        @endforeach

        <span class="flex-1"></span>

        <button
            type="button"
            wire:click="toggleArchived"
            class="{{ $showArchived ? $chipOnClass : $btnClass }} cursor-pointer px-2.5 py-1 text-xs leading-none"
            aria-pressed="{{ $showArchived ? 'true' : 'false' }}"
            title="{{ $showArchived ? __('devboard::t.back_to_active') : __('devboard::t.show_archived') }}"
        ><x-devboard::icon name="archive" /> {{ $showArchived ? __('devboard::t.back_to_active') : __('devboard::t.archived') }} ({{ $archivedCount }})</button>
    </div>

    @if ($showArchived)
        <p class="text-xs opacity-70">{{ __('devboard::t.archive_help') }}</p>
    @elseif ($filtering)
        <p class="text-xs opacity-70">{{ __('devboard::t.filter_help') }}</p>
    @endif
</div>
