{{--
    Form inline di rinomina di un todo (sostituisce il titolo mentre è in modifica).
    Variabili attese: $inputClass, $cancelClass (più $titleDraft, $titleOriginal del componente).
    Niente ✓ e niente ✕ (task 438): si salva da solo, si chiude con Invio, Esc o un clic fuori, e
    finché il testo è cambiato compare il passo indietro alla versione di partenza.
--}}
<form
    wire:submit="finishEdit"
    x-data="{ len: 0 }"
    x-init="len = $el.querySelector('input').value.length"
    x-on:click.outside="$wire.set('titleDraft', $el.querySelector('input').value).then(() => $wire.finishEdit())"
    class="flex min-w-0 flex-1 items-center gap-2"
>
    <input
        type="text"
        wire:model.live.debounce.600ms="titleDraft"
        x-on:blur="$wire.set('titleDraft', $event.target.value)"
        maxlength="{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}"
        x-on:input="len = $event.target.value.length"
        wire:keydown.escape="finishEdit"
        x-init="$el.focus(); $el.select()"
        class="{{ $inputClass }} w-full min-w-0 flex-1"
    >
    <span class="shrink-0 text-xs tabular-nums opacity-60" x-text="len + '/{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
    <x-griglia::autosaved />
    @if ($titleOriginal !== null && trim($titleDraft) !== $titleOriginal)
        <button type="button" wire:click="revertEdit" class="{{ $cancelClass }}" title="{{ __('griglia::t.revert') }}" aria-label="{{ __('griglia::t.revert') }}"><x-griglia::icon name="undo" /></button>
    @endif
</form>
