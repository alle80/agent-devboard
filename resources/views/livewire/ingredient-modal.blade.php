<div>
    @if ($open && $todo)
        <div
            class="modal-shell fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.close()"
        >
            {{-- Sfondo scuro --}}
            <div class="absolute inset-0 bg-black/70" wire:click="close"></div>

            {{-- Pannello --}}
            <div class="tl-card tl-modal modal-panel relative w-full max-w-md">

                {{-- Testata --}}
                <div class="modal-head tl-modal-head flex items-center justify-between gap-3 px-5 py-3">
                    <h2 class="tl-display tl-title text-2xl">
                        <x-theme-icon :theme="$t" /> @include('devboard::livewire.partials.modal-title')
                    </h2>
                    ('"devboard::livewire.partials.modal-actions"')
                    <button
                        wire:click="close"
                        class="tl-check tl-display flex size-9 shrink-0 cursor-pointer items-center justify-center transition active:translate-y-px"
                    >✕</button>
                </div>

                <div class="modal-body max-h-[60vh] space-y-4 overflow-y-auto px-5 py-5">

                    @include('devboard::livewire.partials.modal-readonly')

                    {{-- Domande dell'assistente (in cima: se ci sono, sono la prima cosa da vedere) --}}
                    @include('devboard::livewire.partials.modal-questions', [
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent',
                        'textClass' => '',
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1.5 active:translate-y-px',
                    ])

                    {{-- Nota --}}
                    @include('devboard::livewire.partials.modal-parent', [
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent mr-1',
                        'textClass' => 'italic',
                    ])

                    @include('devboard::livewire.partials.modal-notes', [
                        'label' => __('devboard::t.note'),
                        'boxClass' => 'tl-card relative px-4 py-3',
                        'labelClass' => 'tl-display tl-accent mr-1',
                        'textClass' => 'italic',
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1 active:translate-y-px',
                        'cancelClass' => 'tl-check tl-display cursor-pointer px-3 py-1 active:translate-y-px',
                    ])

                    {{-- Immagini allegate --}}
                    @include('devboard::livewire.partials.modal-images', [
                        'labelClass' => 'tl-display tl-accent text-xl',
                        'btnClass' => 'tl-check tl-display inline-flex items-center gap-1 px-2 py-1 text-sm active:translate-y-px',
                        'hintClass' => 'opacity-70',
                        'thumbClass' => 'tl-card',
                    ])

                    {{-- Ingredienti --}}
                    <div>
                        <h3 class="tl-display tl-accent mb-2 text-xl">{{ __('devboard::t.subtasks') }}</h3>
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
                                        @include('devboard::livewire.partials.modal-ingredient-edit', [
                        'inputClass' => 'tl-input px-3 py-2 focus:outline-none',
                        'okClass' => 'tl-check tl-display tl-check-on cursor-pointer px-3 py-1 active:translate-y-px',
                        'cancelClass' => 'tl-check tl-display cursor-pointer px-3 py-1 active:translate-y-px',
                                        ])
                                    @else
                                    @unless($readonly)
                                    <span class="ing-handle shrink-0 cursor-grab touch-none text-lg leading-none opacity-30 transition select-none hover:opacity-100 active:cursor-grabbing" title="{{ __('devboard::t.drag_to_reorder') }}">⠿</span>
                                    @endunless
                                    <button
                                        wire:click="toggleIngredient({{ $ingredient->id }})"
                                        @disabled($readonly)
                                        class="tl-card flex min-w-0 flex-1 cursor-pointer items-center gap-3 px-3 py-2 text-left transition {{ $ingredient->checked ? 'tl-done' : '' }}"
                                    >
                                        <span class="tl-check tl-display flex size-7 shrink-0 items-center justify-center {{ $ingredient->checked ? 'tl-check-on' : '' }}">
                                            {{ $ingredient->checked ? '✔' : '' }}
                                        </span>
                                        <span class="tl-item-title break-words {{ $ingredient->checked ? 'line-through' : '' }}">
                                            {{ $ingredient->name }}
                                        </span>
                                    </button>
                                        @unless($readonly)
                                        <button
                                            wire:click="editIngredient({{ $ingredient->id }})"
                                            title="{{ __('devboard::t.edit_subtask') }}"
                                            class="shrink-0 cursor-pointer text-base opacity-25 transition hover:scale-125 hover:opacity-100"
                                        >✏️</button>
                                        @endunless
                                    @unless($readonly)
                                    <button
                                        wire:click="deleteIngredient({{ $ingredient->id }})"
                                        wire:confirm="{{ str_replace(':title', $ingredient->name, $t['confirm']) }}"
                                        title="{{ __('devboard::t.delete_subtask') }}"
                                        class="shrink-0 cursor-pointer text-lg opacity-25 transition hover:scale-125 hover:opacity-100"
                                    >✕</button>
                                    @endunless
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        {{-- Nuovo ingrediente --}}
                        @unless($readonly)
                        <form wire:submit="addIngredient" class="mt-3 flex items-center gap-2">
                            <input
                                type="text"
                                wire:model="newIngredient"
                                placeholder="{{ $t['placeholder'] }}"
                                class="tl-input w-full min-w-0 flex-1 px-3 py-1.5 focus:outline-none"
                            >
                            <button type="submit" class="tl-check tl-display shrink-0 cursor-pointer px-3 py-1.5 transition active:translate-y-px">+</button>
                        </form>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
