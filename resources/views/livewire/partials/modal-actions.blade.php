{{--
    State badge (coloured) + row commands, for the modal title bar.
    Uses the modal component methods: stateKey(), toggleOpenToWork(), resumeTodo(), archiveTodo(), deleteTodo().
    Vars available from the modal: $todo, $readonly.
--}}
@php($state = $this->stateKey())
<div class="ml-auto flex shrink-0 items-center gap-1.5" style="font-size: 1rem; font-weight: 400; letter-spacing: normal; text-transform: none;">
    {{-- State badge = menu: click to change the state from here too (waiting / open to work / done; stop when working) --}}
    <details class="db-state-menu relative" x-data="{ o: false }" x-bind:open="o" x-on:toggle="o = $el.open" x-on:click.outside="o = false" x-on:keydown.escape.window="o = false">
        <summary class="db-badge db-badge-{{ $state }} db-state-trigger cursor-pointer list-none [&::-webkit-details-marker]:hidden"
                 title="{{ __('devboard::t.state.'.$state) }} — {{ __('devboard::t.state_change') }}"
                 aria-label="{{ __('devboard::t.state.'.$state) }} — {{ __('devboard::t.state_change') }}"
                 aria-haspopup="menu">
            <x-devboard::icon :name="$state" size="1.25em" :stroke="2" /><x-devboard::icon name="chevron" size=".8em" class="opacity-60" />
        </summary>
        <div class="db-state-list absolute right-0 z-30 mt-1 min-w-48 rounded-lg border-2 border-current/30 p-1 text-sm shadow-lg" role="menu" style="font-family: system-ui, sans-serif">
            @foreach (['waiting', 'open', 'done'] as $s)
                <button type="button" role="menuitemradio" aria-checked="{{ $state === $s ? 'true' : 'false' }}"
                        wire:click="setState('{{ $s }}')" x-on:click="o = false"
                        class="db-state-opt flex w-full cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-left {{ $state === $s ? 'db-state-opt-on' : '' }}">
                    <span class="db-badge db-badge-{{ $s }}"><x-devboard::icon :name="$s" size="1.15em" :stroke="2" /></span>
                    <span class="flex-1">{{ __('devboard::t.state.'.$s) }}</span>
                    @if ($state === $s)<x-devboard::icon name="check" size="1em" />@endif
                </button>
            @endforeach
            @if ($state === 'working')
                <p class="px-2 pt-1 text-xs opacity-70">{{ __('devboard::t.state_stop_hint') }}</p>
            @elseif ($state === 'question')
                <p class="px-2 pt-1 text-xs opacity-70">{{ __('devboard::t.state_question_hint') }}</p>
            @endif
        </div>
    </details>

    <span class="mx-0.5 opacity-20" aria-hidden="true">|</span>

    @if ($readonly)
        <button type="button" class="db-cmd" wire:click="resumeTodo"
                title="{{ __('devboard::t.resume') }}" aria-label="{{ __('devboard::t.resume') }}">
            <x-devboard::icon name="resume" />
        </button>
    @endif

    <button type="button" class="db-cmd" wire:click="archiveTodo"
            title="{{ __('devboard::t.archive') }}" aria-label="{{ __('devboard::t.archive') }}">
        <x-devboard::icon name="archive" />
    </button>

    <button type="button" class="db-cmd db-cmd-danger" wire:click="deleteTodo"
            wire:confirm="{{ str_replace(':title', $todo->title, $t['confirm'] ?? __('devboard::t.delete_confirm', ['title' => $todo->title])) }}"
            title="{{ __('devboard::t.delete') }}" aria-label="{{ __('devboard::t.delete') }}">
        <x-devboard::icon name="trash" />
    </button>
</div>
