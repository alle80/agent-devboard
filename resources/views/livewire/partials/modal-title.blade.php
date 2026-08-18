{{--
    Titolo del todo nella testata del modale: tocca/click per rinominare (max TITLE_MAX caratteri).
    Eredita font e colore dall'h2 dello stile che lo include. Variabili: $todo, $titleDraft, $readonly.
--}}
@if ($titleDraft !== null && ! $readonly)
    <form wire:submit="saveTitle" x-data="{ len: {{ mb_strlen($titleDraft) }} }" class="flex min-w-0 flex-1 items-center gap-2" style="font: inherit; color: inherit">
        <input
            type="text"
            wire:model="titleDraft"
            wire:keydown.escape="cancelTitle"
            maxlength="{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}"
            x-init="$el.focus(); $el.select()"
            x-on:input="len = $event.target.value.length"
            aria-label="{{ __('devboard::t.new_title') }}"
            class="w-full min-w-0 flex-1 border-0 border-b-2 border-current bg-transparent px-1 focus:outline-none"
            style="font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit"
        >
        <span class="shrink-0 text-xs font-normal tabular-nums opacity-70" style="font-family: system-ui, sans-serif" x-text="len + '/{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
        <button type="submit" class="shrink-0 cursor-pointer text-lg leading-none" title="{{ __('devboard::t.save') }}" aria-label="{{ __('devboard::t.save_title') }}">✔</button>
        <button type="button" wire:click="cancelTitle" class="shrink-0 cursor-pointer text-lg leading-none opacity-70" title="{{ __('devboard::t.cancel') }}" aria-label="{{ __('devboard::t.cancel') }}">✕</button>
    </form>
@elseif ($readonly)
    <span class="break-words">{{ $todo->title }}</span>
@else
    <button
        type="button"
        wire:click="editTitle"
        title="{{ __('devboard::t.title_tap') }}"
        aria-label="{{ __('devboard::t.title_rename', ['title' => $todo->title]) }}"
        class="group inline min-w-0 cursor-text break-words text-left"
        style="font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit"
    >{{ $todo->title }} <span class="ml-1 inline-block text-[0.55em] align-middle opacity-40 transition group-hover:opacity-100" aria-hidden="true">✏️</span></button>
@endif
