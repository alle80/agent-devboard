<div class="tl-page relative mx-auto {{ ($wide ?? false) ? 'max-w-5xl' : 'max-w-2xl' }} px-4 py-10">

    {{-- ===== HEADER ===== --}}
    <header class="relative mb-10 text-center">
        <div class="tl-card inline-block px-7 py-4">
            <h1 class="tl-display tl-title"><x-devboard::theme-icon :theme="$t" size="1.1em" /> {{ $listName }} <x-devboard::theme-icon :theme="$t" size="1.1em" class="max-sm:hidden" /></h1>
            <p class="tl-claim mt-1.5">{{ $t['claim'] }}</p>
        </div>

        @php($done = $todos->where('completed', true)->count())
        <div class="mt-6">
            <span class="tl-card tl-display tl-counter inline-block px-4 py-1">
                {{ $done }}/{{ $todos->count() }} {{ $t['counter'] }}{{ $done === $todos->count() && $todos->isNotEmpty() ? ' — '.$t['done_all'] : '' }}
            </span>
        </div>
    </header>

    {{-- Ricerca, filtri, archivio --}}
    @include('devboard::livewire.partials.list-toolbar', [
        'wrapClass' => '',
        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
        'chipClass' => 'tl-check tl-display',
        'chipOnClass' => 'tl-check tl-check-on tl-display',
        'btnClass' => 'tl-check tl-display',
    ])

    {{-- ===== LISTA (riordinabile con drag & drop sulla maniglia) ===== --}}
    <div
        class="space-y-0"
        x-data
        x-init="
            Sortable.create($el, {
                handle: '.drag-handle',
                draggable: '[data-todo-id]',
                animation: 150,
                ghostClass: 'opacity-30',
                onEnd: () => $wire.reorder(
                    Array.from($el.querySelectorAll('[data-todo-id]')).map(el => el.dataset.todoId)
                ),
            })
        "
    >
        @foreach ($todos as $todo)
        <div wire:key="todo-{{ $todo->id }}" data-todo-id="{{ $todo->id }}">

            {{-- Separatore "+" per inserire PRIMA di questo todo --}}
            <div>
                @if ($insertAt === $todo->order)
                    @include('devboard::livewire.partials.insert-form')
                @else
                    <div class="group flex h-6 items-center justify-center">
                        <button
                            wire:click="$dispatch('open-new-task', { position: {{ $todo->order }} })"
                            title="{{ __('devboard::t.insert_here') }}"
                            class="tl-num cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100 active:translate-y-px"
                        >+</button>
                    </div>
                @endif
            </div>

            {{-- Riga todo --}}
            @php($unseen = $todo->completed && ! $todo->result_seen)
            <div class="tl-card todo-row relative my-1.5 flex items-center gap-3 px-3 py-2.5 transition sm:px-4 {{ $todo->completed ? 'tl-done' : '' }} {{ $unseen ? 'db-unseen' : '' }}">

                @if ($todo->working && $todo->progress !== null)
                    <span class="db-progress-track" aria-hidden="true"></span>
                    <span class="db-progress-bar" style="width: {{ $todo->progress }}%" aria-hidden="true"></span>
                @endif

                <span
                    class="drag-handle shrink-0 cursor-grab touch-none text-xl leading-none opacity-30 transition select-none hover:opacity-100 active:cursor-grabbing"
                    title="{{ __('devboard::t.drag_to_reorder') }}"
                >⠿</span>

                <span class="tl-num tl-display shrink-0">{{ $todo->order }}</span>

                {{-- Checkbox --}}
                <button
                    wire:click="toggle({{ $todo->id }})"
                    class="tl-check tl-display flex size-8 shrink-0 cursor-pointer items-center justify-center transition active:translate-y-px {{ $todo->completed ? 'tl-check-on' : '' }}"
                >{{ $todo->completed ? '✔' : '' }}</button>

                {{-- Titolo (cliccabile se ha ingredienti o note) --}}
                <div class="todo-title min-w-0 flex-1">
                    @if ($editingId === $todo->id)
                        @include('devboard::livewire.partials.todo-title-edit', [
                            'inputClass' => 'tl-input px-3 py-1.5 focus:outline-none',
                            'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1.5 active:translate-y-px',
                            'cancelClass' => 'tl-check tl-display cursor-pointer px-3 py-1.5 active:translate-y-px',
                        ])
                    @else
                    <button
                            wire:click="$dispatch('open-ingredients', { todoId: {{ $todo->id }} })"
                            class="group/t flex w-full cursor-pointer items-center gap-2 text-left"
                        >
                            <span class="tl-item-title break-words underline decoration-dotted underline-offset-4 {{ $todo->completed ? 'line-through' : '' }}">
                                {{ $todo->title }}
                            </span>
                                <span class="tl-mini shrink-0">
                                    ☑ {{ $todo->ingredients->where('checked', true)->count() }}/{{ $todo->ingredients->count() }}
                                </span>
                            @if ($todo->notes)
                                <span class="shrink-0" title="{{ $todo->notes }}">💬</span>
                            @endif
                            @if ($todo->claude_comment)
                                <span class="shrink-0 text-sm" title="{{ __('devboard::t.agent_replied') }}">🤖</span>
                            @endif
                            @if ($todo->attachments_count)
                                <span class="shrink-0 text-sm" title="{{ __('devboard::t.images_count', ['count' => $todo->attachments_count]) }}">📷{{ $todo->attachments_count }}</span>
                            @endif
                            @if ($unseen)
                                <span class="db-unseen-badge shrink-0" title="{{ __('devboard::t.result_new_hint') }}">{{ __('devboard::t.result_new') }}</span>
                            @endif
                            @if ($todo->working && $todo->progress !== null)
                                <span class="db-progress-pct shrink-0 tabular-nums" title="{{ __('devboard::t.progress') }}">{{ $todo->progress }}%</span>
                            @endif
                    </button>
                    @endif
                </div>

                    @if ($editingId !== $todo->id)
                    @php($st = $todo->question ? 'question' : ($todo->working ? 'working' : ($todo->open_to_work ? 'open' : 'waiting')))
                    <button
                        wire:click="toggleOpenToWork({{ $todo->id }})"
                        @if ($todo->working) wire:confirm="{{ __('devboard::t.stop_confirm', ['title' => $todo->title]) }}" @endif
                        title="{{ $todo->question ? __('devboard::t.dot_question') : ($todo->working ? __('devboard::t.dot_working') : ($todo->open_to_work ? __('devboard::t.dot_otw_on') : __('devboard::t.dot_otw_off'))) }}"
                        class="todo-action db-badge db-badge-{{ $st }} shrink-0 cursor-pointer transition hover:scale-125 {{ $st === 'waiting' ? 'opacity-40 hover:opacity-100' : '' }}"
                    ><x-devboard::icon :name="$st" size="1.2em" :stroke="2" /></button>
                    <button
                        wire:click="startEdit({{ $todo->id }})"
                        title="{{ __('devboard::t.rename') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-devboard::icon name="edit" size="1.05em" /></button>
                    @endif

                {{-- Elimina --}}
                @if ($showArchived)
                    <button
                        wire:click="unarchive({{ $todo->id }})"
                        title="{{ __('devboard::t.restore') }}"
                        aria-label="{{ __('devboard::t.restore') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-devboard::icon name="restore" size="1.05em" /></button>
                @else
                    <button
                        wire:click="archive({{ $todo->id }})"
                        title="{{ __('devboard::t.archive_hint') }}"
                        aria-label="{{ __('devboard::t.archive') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-devboard::icon name="archive" size="1.05em" /></button>
                @endif
                    @if ($todo->completed)
                        <button
                            wire:click="resume({{ $todo->id }})"
                            title="{{ __('devboard::t.resume_hint') }}"
                            aria-label="{{ __('devboard::t.resume') }}"
                            class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                        ><x-devboard::icon name="resume" size="1.05em" /></button>
                    @endif
                <button
                    wire:click="delete({{ $todo->id }})"
                    wire:confirm="{{ str_replace(':title', $todo->title, $t['confirm']) }}"
                    title="{{ __('devboard::t.delete') }}"
                    class="todo-action db-cmd-danger shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                ><x-devboard::icon name="trash" size="1.05em" /></button>
            </div>
        </div>
        @endforeach

        {{-- Inserimento in coda --}}
        @php($endPos = ($todos->last()?->order ?? 0) + 1)
        <div wire:key="gap-end" class="pt-5">
            @unless ($showArchived)
            @if ($insertAt === $endPos)
                @include('devboard::livewire.partials.insert-form')
            @else
                <div class="text-center">
                    <button
                        wire:click="$dispatch('open-new-task')"
                        class="tl-card tl-display tl-add inline-block cursor-pointer px-6 py-2 transition hover:scale-105 active:translate-y-0.5"
                    >{{ $t['add'] }}</button>
                </div>
            @endif
            @endunless
        </div>
    </div>

    <footer class="tl-display tl-footer mt-14 text-center opacity-70">
        {{ $t['footer'] }}
    </footer>

    {{-- wire:key: senza chiave stabile il modale viene ricreato (perdendo open=true) quando la lista
         si ri-renderizza dopo aver aggiunto una riga → il pulsante «nuovo task» non apriva il modale. --}}
    <livewire:devboard::themed-ingredient-modal :theme="$theme" wire:key="devboard-ingredient-modal" />
</div>
