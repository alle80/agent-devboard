{{--
    Titolo del todo nella testata del modale: tocca/click per rinominare (max TITLE_MAX caratteri).
    Eredita font e colore dall'h2 dello stile che lo include.
    Variabili: $todo, $titleDraft, $titleOriginal, $readonly.
    Niente ✓ e niente ✕ (task 438): si salva da solo, si chiude con Invio, Esc o un clic fuori, e
    finché il testo è cambiato compare il passo indietro alla versione di partenza.
--}}
@if ($titleDraft !== null && ! $readonly)
    <form
        wire:submit="finishTitle"
        x-data="{ len: {{ mb_strlen($titleDraft) }} }"
        x-on:click.outside="$wire.set('titleDraft', $el.querySelector('input').value).then(() => $wire.finishTitle())"
        class="flex min-w-0 flex-1 items-center gap-2"
        style="font: inherit; color: inherit"
    >
        <input
            type="text"
            wire:model.live.debounce.600ms="titleDraft"
            x-on:blur="$wire.set('titleDraft', $event.target.value)"
            wire:keydown.escape="finishTitle"
            maxlength="{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}"
            x-init="$el.focus(); $el.select()"
            x-on:input="len = $event.target.value.length"
            aria-label="{{ __('griglia::t.new_title') }}"
            class="w-full min-w-0 flex-1 border-0 border-b-2 border-current bg-transparent px-1 focus:outline-none"
            style="font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit"
        >
        <x-griglia::mic class="shrink-0 text-base leading-none opacity-70 hover:opacity-100" within="form" target="input" />
        <span class="shrink-0 text-xs font-normal tabular-nums opacity-70" style="font-family: system-ui, sans-serif" x-text="len + '/{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
        <x-griglia::autosaved />
        @if ($titleOriginal !== null && trim($titleDraft) !== $titleOriginal)
            <button type="button" wire:click="revertTitle" class="shrink-0 cursor-pointer text-lg leading-none opacity-70 hover:opacity-100" title="{{ __('griglia::t.revert') }}" aria-label="{{ __('griglia::t.revert') }}"><x-griglia::icon name="undo" /></button>
        @endif
    </form>
@elseif ($readonly)
    <span class="break-words">{{ $todo->title }}</span>
@else
    <button
        type="button"
        wire:click="editTitle"
        title="{{ __('griglia::t.title_tap') }}"
        aria-label="{{ __('griglia::t.title_rename', ['title' => $todo->title]) }}"
        class="group inline min-w-0 cursor-text break-words text-left"
        style="font: inherit; color: inherit; letter-spacing: inherit; text-transform: inherit"
    >{{ $todo->title }} <span class="ml-1 inline-block text-[0.55em] align-middle opacity-40 transition group-hover:opacity-100" aria-hidden="true"><x-griglia::icon name="edit" /></span></button>
@endif
