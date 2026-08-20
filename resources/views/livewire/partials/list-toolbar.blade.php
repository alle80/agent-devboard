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
    {{-- Multi-agent: default agent of this list --}}
    @if (\Alle80\Griglia\Agent::many())
        <div class="db-list-agent flex flex-wrap items-center gap-2 text-sm">
            <label class="font-bold" for="list-agent">{{ __('griglia::t.agent_of_list') }}</label>
            <select id="list-agent" class="{{ $inputClass }} text-xs" wire:change="setListAgent($event.target.value)">
                <option value="" @selected(($listAgent ?? '') === '')>{{ __('griglia::t.agent_default', ['agent' => \Alle80\Griglia\Agent::label(\Alle80\Griglia\Agent::defaultKey())]) }}</option>
                @foreach (\Alle80\Griglia\Agent::all() as $k => $label)
                    <option value="{{ $k }}" @selected(($listAgent ?? '') === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Plan mode: start the plan / progress --}}
    @if (! empty($plan))
        <div class="db-plan-bar flex flex-wrap items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-1 font-bold"><x-griglia::icon name="ruler" /> {{ __('griglia::t.plan.label') }}</span>
            <span class="tabular-nums opacity-80">{{ __('griglia::t.plan.progress', ['done' => $plan['done'], 'total' => $plan['total']]) }}</span>
            @if ($plan['running'])
                <span class="inline-flex items-center gap-1 text-xs opacity-80"><span class="db-badge db-badge-working"><x-griglia::icon name="working" /></span> {{ __('griglia::t.plan.running') }}</span>
                <button type="button" wire:click="pausePlan" class="{{ $btnClass }} inline-flex cursor-pointer items-center gap-1 px-2.5 py-1 text-xs leading-none">
                    <x-griglia::icon name="pause" /> {{ __('griglia::t.plan.pause') }}
                </button>
            @elseif ($plan['next'])
                @if ($plan['paused'])<span class="text-xs opacity-80">{{ __('griglia::t.plan.paused_label') }}</span>@endif
                <button type="button" wire:click="startPlan" class="{{ $btnClass }} inline-flex cursor-pointer items-center gap-1 px-2.5 py-1 text-xs leading-none">
                    <x-griglia::icon name="play" /> {{ $plan['done'] > 0 || $plan['paused'] ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}
                </button>
            @elseif ($plan['done'] === $plan['total'] && $plan['total'] > 0)
                <span class="inline-flex items-center gap-1 text-xs opacity-80"><x-griglia::icon name="done" /> {{ __('griglia::t.plan.completed') }}</span>
            @endif
            {{-- L'obiettivo del piano si corregge dalla sua pagina (task 344) --}}
            <a href="{{ route('griglia.plans.edit', ['list' => \Alle80\Griglia\Models\Checklist::currentId()]) }}"
               class="{{ $btnClass }} inline-flex cursor-pointer items-center gap-1 px-2.5 py-1 text-xs leading-none"
               title="{{ __('griglia::t.plan.edit_title') }}">
                <x-griglia::icon name="edit" /> {{ __('griglia::t.plan.edit_title') }}
            </a>
        </div>
    @endif
    {{-- Ricerca --}}
    <div class="relative">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('griglia::t.search_placeholder') }}"
            autocomplete="off"
            aria-label="{{ __('griglia::t.search_label') }}"
            class="{{ $inputClass }} w-full pr-9 pl-3"
        >
        @if (trim($search) !== '')
            <button
                type="button"
                wire:click="clearSearch"
                title="{{ __('griglia::t.clear_search') }}"
                aria-label="{{ __('griglia::t.clear_search') }}"
                class="absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer text-lg opacity-50 hover:opacity-100"
            ><x-griglia::icon name="close" /></button>
        @endif
    </div>

    {{-- Filtri di stato + archivio --}}
    <div class="flex flex-wrap items-center gap-1.5">
        @php($icons = ['todo' => 'waiting', 'done' => 'done', 'otw' => 'open', 'working' => 'working', 'question' => 'question'])
        @foreach (\Alle80\Griglia\Livewire\TodoList::filters() as $key => $label)
            <button
                type="button"
                wire:click="setFilter('{{ $key }}')"
                class="{{ $filter === $key ? $chipOnClass : $chipClass }} inline-flex cursor-pointer items-center gap-1 px-2.5 py-1 text-xs leading-none"
                aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
            >@isset($icons[$key])<span class="db-badge db-badge-{{ $icons[$key] }}"><x-griglia::icon :name="$icons[$key]" size="1.1em" :stroke="2" /></span>@endisset{{ $label }}</button>
        @endforeach

        <span class="flex-1"></span>

        <button
            type="button"
            wire:click="toggleArchived"
            class="{{ $showArchived ? $chipOnClass : $btnClass }} cursor-pointer px-2.5 py-1 text-xs leading-none"
            aria-pressed="{{ $showArchived ? 'true' : 'false' }}"
            title="{{ $showArchived ? __('griglia::t.back_to_active') : __('griglia::t.show_archived') }}"
        ><x-griglia::icon name="archive" /> {{ $showArchived ? __('griglia::t.back_to_active') : __('griglia::t.archived') }} ({{ $archivedCount }})</button>
    </div>

    @if ($showArchived)
        <p class="text-xs opacity-70">{{ __('griglia::t.archive_help') }}</p>
    @elseif ($filtering)
        <p class="text-xs opacity-70">{{ __('griglia::t.filter_help') }}</p>
    @endif
</div>
