{{--
    State badge (coloured) + row commands, for the modal title bar.
    Uses the modal component methods: stateKey(), toggleOpenToWork(), resumeTodo(), archiveTodo(), deleteTodo().
    Vars available from the modal: $todo, $readonly.
--}}
@php($state = $this->stateKey())
<div class="ml-auto flex shrink-0 items-center gap-1.5" style="font-size: 1rem; font-weight: 400; letter-spacing: normal; text-transform: none;">
    {{-- State badge = the same toggle as the dot in the row: tap → waiting ⚪ ⇄ open to work 🟢
         (working 🔧 → tap = stop; question ❓ → answer below; done ✔ → reopen from the list) --}}
    @php($next = match ($state) { 'waiting' => 'open', 'open' => 'waiting', 'working' => 'waiting', default => null })
    <button type="button"
            class="db-badge db-badge-{{ $state }} db-state-trigger {{ $next ? 'cursor-pointer transition hover:scale-110 active:translate-y-px' : 'cursor-default' }}"
            @if ($next) wire:click="setState('{{ $next }}')" @endif
            @if ($state === 'working') wire:confirm="{{ __('devboard::t.stop_confirm', ['title' => $todo->title]) }}" @endif
            title="{{ __('devboard::t.state.'.$state) }}{{ $next ? ' — '.__('devboard::t.state_tap', ['state' => __('devboard::t.state.'.$next)]) : '' }}"
            aria-label="{{ __('devboard::t.state.'.$state) }}{{ $next ? ' — '.__('devboard::t.state_tap', ['state' => __('devboard::t.state.'.$next)]) : '' }}"
            @unless ($next) aria-disabled="true" @endunless>
        <x-devboard::icon :name="$state" size="1.25em" :stroke="2" />
    </button>

    <span class="mx-0.5 opacity-20" aria-hidden="true">|</span>

    @if ($readonly)
        <button type="button" class="db-cmd" wire:click="resumeTodo"
                title="{{ __('devboard::t.resume') }}" aria-label="{{ __('devboard::t.resume') }}">
            <x-devboard::icon name="resume" />
        </button>
    @endif

    @if ($otherLists->isNotEmpty())
        {{-- Move to another list --}}
        <details class="relative" x-data="{ o: false }" x-bind:open="o" x-on:toggle="o = $el.open" x-on:click.outside="o = false" x-on:keydown.escape.window="o = false">
            <summary class="db-cmd cursor-pointer list-none [&::-webkit-details-marker]:hidden" title="{{ __('devboard::t.move_to') }}" aria-label="{{ __('devboard::t.move_to') }}" aria-haspopup="menu">
                <x-devboard::icon name="move" />
            </summary>
            <div class="db-menu absolute right-0 z-30 mt-1 max-h-60 min-w-48 overflow-y-auto rounded-lg border-2 border-current/30 p-1 text-sm shadow-lg" role="menu" style="font-family: system-ui, sans-serif">
                <p class="px-2 py-1 text-xs uppercase tracking-wide opacity-60">{{ __('devboard::t.move_to') }}</p>
                @foreach ($otherLists as $l)
                    <button type="button" role="menuitem" wire:click="moveTo({{ $l->id }})" x-on:click="o = false"
                            class="db-menu-item flex w-full cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-left"><x-devboard::icon name="list" /> <span class="truncate">{{ $l->name }}</span></button>
                @endforeach
            </div>
        </details>
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
