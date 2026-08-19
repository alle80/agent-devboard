{{--
    State badge (coloured) + row commands, for the modal title bar.
    Uses the modal component methods: stateKey(), toggleOpenToWork(), resumeTodo(), archiveTodo(), deleteTodo().
    Vars available from the modal: $todo, $readonly.
--}}
@php($state = $this->stateKey())
<div class="ml-auto flex shrink-0 items-center gap-1.5" style="font-size: 1rem; font-weight: 400; letter-spacing: normal; text-transform: none;">
    <span class="db-badge db-badge-{{ $state }}" title="{{ __('devboard::t.state.'.$state) }}" aria-label="{{ __('devboard::t.state.'.$state) }}">
        <x-devboard::icon :name="$state" size="1.25em" :stroke="2" />
    </span>

    <span class="mx-0.5 opacity-20" aria-hidden="true">|</span>

    @if ($readonly)
        <button type="button" class="db-cmd" wire:click="resumeTodo"
                title="{{ __('devboard::t.resume') }}" aria-label="{{ __('devboard::t.resume') }}">
            <x-devboard::icon name="resume" />
        </button>
    @else
        <button type="button" class="db-cmd" wire:click="toggleOpenToWork"
                title="{{ $todo->open_to_work ? __('devboard::t.dot_otw_off') : __('devboard::t.dot_otw_on') }}"
                aria-label="{{ __('devboard::t.dot_otw_on') }}">
            <x-devboard::icon name="open" />
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
