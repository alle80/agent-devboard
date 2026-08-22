<div class="tl-page relative mx-auto {{ ($wide ?? false) ? 'max-w-5xl' : 'max-w-2xl' }} px-4 pt-20 pb-10 sm:pt-24">

    {{-- ===== HEADER ===== --}}
    @php($done = $todos->where('completed', true)->count())
    @php($total = $todos->count())
    <header class="relative mb-8 text-center">
        <div class="tl-card inline-block px-5 py-2.5">
            <h1 class="tl-display tl-title">{{ $listName }}</h1>
            @if (! empty($t['claim']))
                <p class="tl-claim mt-1">{{ $t['claim'] }}</p>
            @endif
        </div>

        {{-- Avanzamento della lista: stessa hairline del menu delle liste --}}
        <div class="mx-auto mt-5 max-w-xs">
            <p class="tl-display tl-counter">
                <span class="tabular-nums">{{ $done }}/{{ $total }}</span> {{ $t['counter'] }}{{ $done === $total && $todos->isNotEmpty() ? ' — '.$t['done_all'] : '' }}
            </p>
            @if ($total > 0)
                <span class="tl-meter mt-2 block" role="img" aria-label="{{ $done }}/{{ $total }} {{ $t['counter'] }}"><span style="width: {{ (int) round($done / max($total, 1) * 100) }}%"></span></span>
            @endif
        </div>
    </header>

    {{-- Ricerca, filtri, archivio --}}
    @include('griglia::livewire.partials.list-toolbar', [
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
                    @include('griglia::livewire.partials.insert-form')
                @else
                    <div class="group flex h-6 items-center justify-center">
                        <button
                            wire:click="$dispatch('open-new-task', { position: {{ $todo->order }} })"
                            title="{{ __('griglia::t.insert_here') }}"
                            class="tl-num cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100 active:translate-y-px"
                        >+</button>
                    </div>
                @endif
            </div>

            {{-- Riga todo --}}
            {{-- Bordo colorato finché la riga chiede attenzione: verde/giallo/rosso secondo l'esito, viola se ci sono domande.
                 Il colore è scritto INLINE, non solo nelle classi: le viste arrivano da vendor/ mentre il CSS passa da una
                 build dell'app, e finché le due versioni non coincidono le regole `.db-attention` possono mancare (o essere
                 quelle vecchie) — il bordo non si vedeva affatto. Inline vince anche sul filtro grigio di `.tl-done`. --}}
            @php($attention = $todo->attention())
            @php($unseen = $attention && $attention !== 'question')
            @php($attentionColor = $todo->attentionColor())
            <div
                class="tl-card todo-row relative my-1.5 flex items-center gap-3 px-3 py-2.5 transition sm:px-4 {{ $todo->completed ? 'tl-done' : '' }} {{ $attention ? 'db-attention db-att-'.$attention : '' }}"
                @if ($attention)
                    style="--db-att: {{ $attentionColor }}; border-color: {{ $attentionColor }}; border-style: solid; border-width: max(var(--tl-bw, 2px), 2px); outline: none; opacity: 1; filter: none;"
                    title="{{ __('griglia::t.result_'.($attention === 'ok' ? 'new' : $attention).'_hint') }}"
                @endif
            >

                @if ($todo->working && $todo->progress !== null)
                    <span class="db-progress-track" aria-hidden="true"></span>
                    <span class="db-progress-bar" style="width: {{ $todo->progress }}%" aria-hidden="true"></span>
                @endif

                <span
                    class="drag-handle shrink-0 cursor-grab touch-none text-xl leading-none opacity-30 transition select-none hover:opacity-100 active:cursor-grabbing"
                    title="{{ __('griglia::t.drag_to_reorder') }}"
                >⠿</span>

                <span class="tl-num tl-display shrink-0">{{ $todo->order }}</span>

                {{-- Checkbox --}}
                <button
                    wire:click="toggle({{ $todo->id }})"
                    @disabled($todo->working)
                    class="tl-check tl-display flex size-8 shrink-0 cursor-pointer items-center justify-center transition active:translate-y-px {{ $todo->completed ? 'tl-check-on' : '' }}"
                >@if ($todo->completed)<x-griglia::icon name="check" :stroke="3" />@endif</button>

                {{-- Titolo (cliccabile se ha ingredienti o note) --}}
                <div class="todo-title min-w-0 flex-1">
                    @if ($editingId === $todo->id)
                        @include('griglia::livewire.partials.todo-title-edit', [
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
                                @if ($searchAllLists && trim($search) !== '')
                                    <span class="ml-1 text-[.7em] font-normal opacity-60">{{ $todo->checklist->name }}</span>
                                @endif
                            </span>
                            @if ($todo->ingredients->isNotEmpty())
                                <span class="tl-mini shrink-0">
                                    <x-griglia::icon name="tasks" /> {{ $todo->ingredients->where('checked', true)->count() }}/{{ $todo->ingredients->count() }}
                                </span>
                            @endif
                            @if ($todo->claude_comment)
                                <span class="shrink-0 text-sm opacity-80" title="{{ __('griglia::t.agent_replied') }}"><x-griglia::icon name="bot" /></span>
                            @endif
                            @if ($todo->attachments_count)
                                <span class="inline-flex shrink-0 items-center gap-0.5 text-sm" title="{{ __('griglia::t.images_count', ['count' => $todo->attachments_count]) }}"><x-griglia::icon name="image" />{{ $todo->attachments_count }}</span>
                            @endif
                            {{-- Niente targhetta visibile: l'utente ha chiesto il bordo colorato e basta (task 415).
                                 Il testo resta per chi legge con uno screen reader, che il colore non lo vede. --}}
                            @if ($attention)
                                <span class="sr-only">{{ __('griglia::t.result_'.($attention === 'ok' ? 'new' : $attention)) }}</span>
                            @endif
                            @if ($todo->depends_on_id)
                                <span class="db-chain shrink-0 text-xs opacity-70" title="{{ __('griglia::t.plan.after', ['title' => $todo->dependsOn?->title ?? '#'.$todo->depends_on_id]) }}"><x-griglia::icon name="link" />@if ($todo->dependsOn?->completed)<x-griglia::icon name="check" size=".9em" />@endif</span>
                            @endif
                            @if ($todo->working && $todo->progress !== null)
                                <span class="db-progress-pct shrink-0 tabular-nums" title="{{ __('griglia::t.progress') }}">{{ $todo->progress }}%</span>
                                @if ($todo->phase)
                                    <span class="db-phase min-w-0 truncate text-xs italic opacity-75" title="{{ $todo->phase }}">{{ $todo->phase }}</span>
                                @endif
                            @endif
                            {{-- Id del task (task 510): lo stesso «id:N» che l'agente stampa in griglia:check, ultima targhetta
                                 del titolo, spinta a destra (ml-auto): sul telefono va a capo da sola invece di rubare spazio ai
                                 comandi del primo livello (in v0.87.0 li mandava su due righe). È uno span dentro il bottone del
                                 titolo: copy.js intercetta il tocco in fase di cattura e copia il numero senza aprire il modale. --}}
                            <span class="db-id ml-auto shrink-0" data-copy="{{ $todo->id }}" title="{{ __('griglia::t.task_id_copy', ['id' => $todo->id]) }}">id:{{ $todo->id }}</span>
                    </button>
                    @if ($todo->claude_comment && $todo->result_summary)
                        <p class="mt-0.5 truncate text-xs opacity-60" title="{{ \Alle80\Griglia\Support\Markdown::normalizeAgentResponse($todo->result_summary) }}">{{ \Alle80\Griglia\Support\Markdown::normalizeAgentResponse($todo->result_summary) }}</p>
                    @endif
                    {{-- Multi-agente: chi lavora questo task. La targhetta sta su una riga sua, SOTTO il titolo
                         (task 427): in mezzo ai comandi era schiacciata fra le icone. Il nome si vede SEMPRE,
                         anche quando il task eredita l'agente della lista (opzione vuota = eredita). --}}
                    @if (\Alle80\Griglia\Agent::many())
                        <div class="db-agent-row mt-1 flex items-center">
                            @include('griglia::livewire.partials.agent-select', [
                                'todo' => $todo,
                                'change' => 'setTodoAgent('.$todo->id.', $event.target.value)',
                                'inheritLabel' => \Alle80\Griglia\Agent::label($todo->agent ?: ($listAgent ?: \Alle80\Griglia\Agent::defaultKey())),
                                'class' => 'db-agent-chip rounded border border-current/40 px-1 text-[10px] uppercase '.($todo->agent ? 'opacity-75' : 'opacity-50'),
                            ])
                        </div>
                    @endif
                    @endif
                </div>

                    @if ($editingId !== $todo->id)
                    @php($st = $todo->completed ? 'done' : ($todo->question ? 'question' : ($todo->working ? 'working' : ($todo->open_to_work ? 'open' : 'waiting'))))
                    <button
                        wire:click="toggleOpenToWork({{ $todo->id }})"
                        @if ($todo->working) wire:confirm="{{ __('griglia::t.stop_confirm', ['title' => $todo->title]) }}" @endif
                        title="{{ $todo->completed ? __('griglia::t.dot_done') : ($todo->question ? __('griglia::t.dot_question') : ($todo->working ? __('griglia::t.dot_working') : ($todo->open_to_work ? __('griglia::t.dot_otw_on') : __('griglia::t.dot_otw_off')))) }}"
                        class="todo-action db-badge db-badge-{{ $st }} shrink-0 cursor-pointer transition hover:scale-125 {{ $st === 'waiting' ? 'opacity-40 hover:opacity-100' : '' }}"
                    ><x-griglia::icon :name="$st" size="1.2em" :stroke="2" /></button>
                    @unless($todo->working)
                    <button
                        wire:click="startEdit({{ $todo->id }})"
                        title="{{ __('griglia::t.rename') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-griglia::icon name="edit" size="1.05em" /></button>
                    @endunless
                    @endif

                {{-- Elimina --}}
                @if ($showArchived)
                    <button
                        wire:click="unarchive({{ $todo->id }})"
                        title="{{ __('griglia::t.restore') }}"
                        aria-label="{{ __('griglia::t.restore') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-griglia::icon name="restore" size="1.05em" /></button>
                @else
                    <button
                        wire:click="archive({{ $todo->id }})"
                        title="{{ __('griglia::t.archive_hint') }}"
                        aria-label="{{ __('griglia::t.archive') }}"
                        class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                    ><x-griglia::icon name="archive" size="1.05em" /></button>
                @endif
                    @if ($todo->completed)
                        <button
                            wire:click="resume({{ $todo->id }})"
                            title="{{ __('griglia::t.resume_hint') }}"
                            aria-label="{{ __('griglia::t.resume') }}"
                            class="todo-action shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                        ><x-griglia::icon name="resume" size="1.05em" /></button>
                    @endif
                <button
                    wire:click="delete({{ $todo->id }})"
                    wire:confirm="{{ str_replace(':title', $todo->title, $t['confirm']) }}"
                    title="{{ __('griglia::t.delete') }}"
                    class="todo-action db-cmd-danger shrink-0 cursor-pointer opacity-30 transition hover:scale-125 hover:opacity-100"
                ><x-griglia::icon name="trash" size="1.05em" /></button>
            </div>
        </div>
        @endforeach

        {{-- Inserimento in coda --}}
        @php($endPos = ($todos->last()?->order ?? 0) + 1)
        <div wire:key="gap-end" class="pt-5">
            @unless ($showArchived)
            @if ($insertAt === $endPos)
                @include('griglia::livewire.partials.insert-form')
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

    @if ($openTodo = session()->pull('griglia_open_todo'))
        {{-- Deep link (notification / bell): open the modal of that todo once the page is ready --}}
        <div wire:ignore x-data x-init="setTimeout(() => Livewire.dispatch('open-ingredients', { todoId: {{ (int) $openTodo }} }), 400)"></div>
    @endif

    <footer class="tl-display tl-footer mt-14 text-center opacity-70">
        {{ $t['footer'] }}
    </footer>

    {{-- wire:key: senza chiave stabile il modale viene ricreato (perdendo open=true) quando la lista
         si ri-renderizza dopo aver aggiunto una riga → il pulsante «nuovo task» non apriva il modale. --}}
    <livewire:griglia::themed-ingredient-modal :theme="$theme" wire:key="griglia-ingredient-modal" />
</div>
