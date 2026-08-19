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
