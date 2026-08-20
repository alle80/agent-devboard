<div>
    @if ($open && $todo)
        <div
            class="modal-shell fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.close()"
            {{-- Frecce ← → : task precedente/successivo, ma non mentre si scrive (task 365) --}}
            x-on:keydown.window.arrow-left="if (! $event.target.closest('input, textarea, select, [contenteditable]') && ! $event.metaKey && ! $event.ctrlKey && ! $event.altKey) { $event.preventDefault(); $wire.goSibling(-1) }"
            x-on:keydown.window.arrow-right="if (! $event.target.closest('input, textarea, select, [contenteditable]') && ! $event.metaKey && ! $event.ctrlKey && ! $event.altKey) { $event.preventDefault(); $wire.goSibling(1) }"
        >
            {{-- Sfondo scuro --}}
            <div class="absolute inset-0 bg-black/70" wire:click="close"></div>

            {{-- Pannello --}}
            <div class="tl-card tl-modal modal-panel relative w-full max-w-md md:max-w-2xl lg:max-w-3xl">

                {{-- Testata: comandi/badge + chiudi (il titolo sta nel corpo, prima di «Task») --}}
                <div class="modal-head tl-modal-head flex items-center gap-3 px-5 py-3">
                    @include('griglia::livewire.partials.modal-actions')
                    <button
                        wire:click="close"
                        class="tl-check tl-display flex size-9 shrink-0 cursor-pointer items-center justify-center transition active:translate-y-px"
                    ><x-griglia::icon name="close" /></button>
                </div>

                <div class="modal-body max-h-[60vh] space-y-4 overflow-y-auto px-5 py-5 md:max-h-[75vh] md:px-7">

                    {{-- Titolo del task, modificabile, come primo campo del corpo --}}
                    <h2 class="tl-display tl-title text-2xl break-words">@include('griglia::livewire.partials.modal-title')</h2>

                    @include('griglia::livewire.partials.modal-readonly')

                    @if ($todo->working && ($todo->progress !== null || $todo->phase))
                        <p class="db-phase-line inline-flex items-center gap-2 text-xs opacity-80"><span class="db-badge db-badge-working"><x-griglia::icon name="working" :stroke="2" /></span>@if ($todo->progress !== null)<span class="db-progress-pct tabular-nums">{{ $todo->progress }}%</span>@endif @if ($todo->phase)<span class="italic">{{ $todo->phase }}</span>@endif</p>
                    @endif

                    @if ($todo->depends_on_id && $todo->dependsOn)
                        {{-- Plan chain: this task opens when the previous one is done --}}
                        <p class="db-chain-line inline-flex items-center gap-1 text-xs opacity-75"><x-griglia::icon name="link" /> {{ __('griglia::t.plan.after', ['title' => $todo->dependsOn->title]) }} — {{ $todo->dependsOn->completed ? __('griglia::t.plan.prev_done') : __('griglia::t.plan.prev_pending') }}</p>
                    @endif

                    {{-- Domande dell'assistente (in cima: se ci sono, sono la prima cosa da vedere) --}}
                    @include('griglia::livewire.partials.modal-questions', [
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent',
                        'textClass' => '',
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1.5 active:translate-y-px',
                    ])

                    {{-- Nota --}}
                    @include('griglia::livewire.partials.modal-parent', [
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent mr-1',
                        'textClass' => 'italic',
                    ])

                    @include('griglia::livewire.partials.modal-notes', [
                        'label' => __('griglia::t.note'),
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent mr-1',
                        'textClass' => 'italic',
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1 active:translate-y-px',
                        'cancelClass' => 'tl-check tl-display cursor-pointer px-3 py-1 active:translate-y-px',
                    ])

                    {{-- Skills dell'agente per questo task (sotto al «prompt») --}}
                    @include('griglia::livewire.partials.modal-skills', [
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent mr-1',
                        'textClass' => '',
                    ])

                    {{-- Immagini allegate --}}
                    @include('griglia::livewire.partials.modal-images', [
                        'labelClass' => 'tl-display tl-accent text-xl',
                        'btnClass' => 'tl-check tl-display inline-flex items-center gap-1 px-2 py-1 text-sm active:translate-y-px',
                        'hintClass' => 'opacity-70',
                        'thumbClass' => 'tl-card',
                    ])

                    {{-- Ingredienti --}}
                    <div>
                        <h3 class="tl-display tl-accent mb-2 text-xl">{{ __('griglia::t.subtasks') }}</h3>
                        <ul class="space-y-2"
                            x-data
                            x-init="
                                Sortable.create($el, {
                                    handle: '.ing-handle',
                                    draggable: '[data-ingredient-id]',
                                    animation: 150,
                                    ghostClass: 'opacity-30',
                                    onEnd: () => $wire.reorderIngredients(
                                        Array.from($el.querySelectorAll('[data-ingredient-id]')).map(el => el.dataset.ingredientId)
                                    ),
                                })
                            "
                        >
                            @foreach ($todo->ingredients as $ingredient)
                                <li wire:key="ing-{{ $ingredient->id }}" data-ingredient-id="{{ $ingredient->id }}" class="flex items-center gap-2">
                                    @if ($editingIngredientId === $ingredient->id)
                                        @include('griglia::livewire.partials.modal-ingredient-edit', [
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1 active:translate-y-px',
                        'cancelClass' => 'tl-check tl-display cursor-pointer px-3 py-1 active:translate-y-px',
                                        ])
                                    @else
                                    @unless($readonly)
                                    <span class="ing-handle shrink-0 cursor-grab touch-none text-lg leading-none opacity-30 transition select-none hover:opacity-100 active:cursor-grabbing" title="{{ __('griglia::t.drag_to_reorder') }}">⠿</span>
                                    @endunless
                                    <button
                                        wire:click="toggleIngredient({{ $ingredient->id }})"
                                        @disabled($readonly)
                                        class="tl-card flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-3 py-2 text-left transition {{ $ingredient->checked ? 'tl-done' : '' }}"
                                    >
                                        <span class="tl-check tl-display flex size-7 shrink-0 items-center justify-center {{ $ingredient->checked ? 'tl-check-on' : '' }}">
                                            @if ($ingredient->checked)<x-griglia::icon name="check" :stroke="3" />@endif
                                        </span>
                                        <span class="tl-item-title db-prose break-words {{ $ingredient->checked ? 'line-through' : '' }}">
                                            {!! \Alle80\Griglia\Support\Markdown::inline($ingredient->name) !!}
                                        </span>
                                    </button>
                                        @unless($readonly)
                                        <button
                                            wire:click="editIngredient({{ $ingredient->id }})"
                                            title="{{ __('griglia::t.edit_subtask') }}"
                                            class="shrink-0 cursor-pointer text-base opacity-25 transition hover:scale-125 hover:opacity-100"
                                        ><x-griglia::icon name="edit" /></button>
                                        @endunless
                                    @unless($readonly)
                                    <button
                                        wire:click="deleteIngredient({{ $ingredient->id }})"
                                        wire:confirm="{{ str_replace(':title', $ingredient->name, $t['confirm']) }}"
                                        title="{{ __('griglia::t.delete_subtask') }}"
                                        class="shrink-0 cursor-pointer text-lg opacity-25 transition hover:scale-125 hover:opacity-100"
                                    ><x-griglia::icon name="close" /></button>
                                    @endunless
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        {{-- Nuovo ingrediente --}}
                        @unless($readonly)
                        <form wire:submit="addIngredient" class="mt-3 space-y-2">
                            <x-griglia::md-editor
                                model="newIngredient"
                                :rows="1"
                                :placeholder="$t['placeholder']"
                                inputClass="tl-input px-3 py-1.5 focus:outline-none"
                            />
                            <div class="flex justify-end">
                                <button type="submit" class="tl-check tl-display cursor-pointer px-3 py-1.5 transition active:translate-y-px">+</button>
                            </div>
                        </form>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
