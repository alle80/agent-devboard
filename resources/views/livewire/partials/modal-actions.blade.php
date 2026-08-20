{{--
    State badge (coloured) + row commands, for the modal title bar.
    Uses the modal component methods: stateKey(), toggleOpenToWork(), resumeTodo(), archiveTodo(), deleteTodo().
    Vars available from the modal: $todo, $readonly.

    Two groups: «nav» (state + previous/next), always next to the close button, and «tools» (agent, move,
    archive, delete), which on a phone drops to its own line — see .modal-cmds in griglia.css (task 399).
--}}
@php($state = $this->stateKey())
<div class="modal-cmds ml-auto flex min-w-0 items-center gap-1.5" style="font-size: 1rem; font-weight: 400; letter-spacing: normal; text-transform: none;">
    <div class="modal-cmds-nav flex shrink-0 items-center gap-1.5">
        {{-- State badge = the same toggle as the dot in the row: tap → waiting ⚪ ⇄ open to work 🟢
             (working 🔧 → tap = stop; question ❓ → tap = take the task back without answering, the questions
             stay recorded; done ✔ → reopen from the list) --}}
        @php($next = match ($state) { 'waiting' => 'open', 'open' => 'waiting', 'working' => 'waiting', 'question' => 'waiting', default => null })
        <button type="button"
                class="db-badge db-badge-{{ $state }} db-state-trigger {{ $next ? 'cursor-pointer transition hover:scale-110 active:translate-y-px' : 'cursor-default' }}"
                @if ($next) wire:click="setState('{{ $next }}')" @endif
                @if ($state === 'working') wire:confirm="{{ __('griglia::t.stop_confirm', ['title' => $todo->title]) }}" @endif
                @if ($state === 'question') wire:confirm="{{ __('griglia::t.question_drop_confirm') }}" @endif
                title="{{ __('griglia::t.state.'.$state) }}{{ $next ? ' — '.__('griglia::t.state_tap', ['state' => __('griglia::t.state.'.$next)]) : '' }}"
                aria-label="{{ __('griglia::t.state.'.$state) }}{{ $next ? ' — '.__('griglia::t.state_tap', ['state' => __('griglia::t.state.'.$next)]) : '' }}"
                @unless ($next) aria-disabled="true" @endunless>
            <x-griglia::icon :name="$state" size="1.25em" :stroke="2" />
        </button>

        {{-- Prev / next task of the list: seguire una catena senza chiudere il modale (task 365) --}}
        @php($prevId = $this->siblingId(-1))
        @php($nextId = $this->siblingId(1))
        @php($position = $this->position())
        @if ($prevId || $nextId)
            <span class="db-sep mx-0.5 opacity-20" aria-hidden="true">|</span>
            <button type="button" class="db-cmd @unless ($prevId) opacity-30 @endunless" @if ($prevId) wire:click="goSibling(-1)" @else disabled aria-disabled="true" @endif
                    title="{{ __('griglia::t.task_prev') }}" aria-label="{{ __('griglia::t.task_prev') }}">
                <x-griglia::icon name="arrow-left" />
            </button>
            <span class="text-xs tabular-nums opacity-60">{{ $position }}/{{ count($this->siblingIds()) }}</span>
            <button type="button" class="db-cmd @unless ($nextId) opacity-30 @endunless" @if ($nextId) wire:click="goSibling(1)" @else disabled aria-disabled="true" @endif
                    title="{{ __('griglia::t.task_next') }}" aria-label="{{ __('griglia::t.task_next') }}">
                <x-griglia::icon name="arrow-right" />
            </button>
        @endif
    </div>

    <span class="db-sep mx-0.5 opacity-20" aria-hidden="true">|</span>

    <div class="modal-cmds-tools flex min-w-0 items-center gap-1.5">
        @if ($readonly)
            <button type="button" class="db-cmd shrink-0" wire:click="resumeTodo"
                    title="{{ __('griglia::t.resume') }}" aria-label="{{ __('griglia::t.resume') }}">
                <x-griglia::icon name="resume" />
            </button>
        @endif

        @if (\Alle80\Griglia\Agent::many())
            {{-- Multi-agent: which agent handles this task --}}
            <select class="db-cmd db-agent-select min-w-0 cursor-pointer bg-transparent text-xs" wire:change="setAgent($event.target.value)" title="{{ __('griglia::t.agent_of_task') }}" aria-label="{{ __('griglia::t.agent_of_task') }}" x-on:click.stop>
                <option value="" @selected(! $todo->agent)>{{ __('griglia::t.agent_default', ['agent' => \Alle80\Griglia\Agent::label($todo->checklist?->agent ?: \Alle80\Griglia\Agent::defaultKey())]) }}</option>
                @foreach (\Alle80\Griglia\Agent::all() as $k => $label)
                    <option value="{{ $k }}" @selected($todo->agent === $k)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        @if ($otherLists->isNotEmpty())
            {{-- Move to another list --}}
            <details class="relative shrink-0" x-data="{ o: false }" x-bind:open="o" x-on:toggle="o = $el.open" x-on:click.outside="o = false" x-on:keydown.escape.window="o = false">
                <summary class="db-cmd cursor-pointer list-none [&::-webkit-details-marker]:hidden" title="{{ __('griglia::t.move_to') }}" aria-label="{{ __('griglia::t.move_to') }}" aria-haspopup="menu">
                    <x-griglia::icon name="move" />
                </summary>
                <div class="db-menu absolute right-0 z-30 mt-1 max-h-60 min-w-48 overflow-y-auto rounded-lg border-2 border-current/30 p-1 text-sm shadow-lg" role="menu" style="font-family: system-ui, sans-serif">
                    <p class="px-2 py-1 text-xs uppercase tracking-wide opacity-60">{{ __('griglia::t.move_to') }}</p>
                    @foreach ($otherLists as $l)
                        <button type="button" role="menuitem" wire:click="moveTo({{ $l->id }})" x-on:click="o = false"
                                class="db-menu-item flex w-full cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-left"><x-griglia::icon name="list" /> <span class="truncate">{{ $l->name }}</span></button>
                    @endforeach
                </div>
            </details>
        @endif

        <button type="button" class="db-cmd shrink-0" wire:click="archiveTodo"
                title="{{ __('griglia::t.archive') }}" aria-label="{{ __('griglia::t.archive') }}">
            <x-griglia::icon name="archive" />
        </button>

        <button type="button" class="db-cmd db-cmd-danger shrink-0" wire:click="deleteTodo"
                wire:confirm="{{ str_replace(':title', $todo->title, $t['confirm'] ?? __('griglia::t.delete_confirm', ['title' => $todo->title])) }}"
                title="{{ __('griglia::t.delete') }}" aria-label="{{ __('griglia::t.delete') }}">
            <x-griglia::icon name="trash" />
        </button>
    </div>
</div>
