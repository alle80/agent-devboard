{{--
    Form inline di rinomina di un todo (sostituisce il titolo mentre è in modifica).
    Variabili attese: $inputClass, $okClass, $cancelClass
--}}
<form wire:submit="saveEdit" x-data="{ len: 0 }" x-init="len = $el.querySelector('input').value.length" class="flex min-w-0 flex-1 items-center gap-2">
    <input
        type="text"
        wire:model="titleDraft"
        maxlength="{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}"
        x-on:input="len = $event.target.value.length"
        wire:keydown.escape="cancelEdit"
        x-init="$el.focus(); $el.select()"
        class="{{ $inputClass }} w-full min-w-0 flex-1"
    >
    <span class="shrink-0 text-xs tabular-nums opacity-60" x-text="len + '/{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
    <button type="submit" class="{{ $okClass }}" title="{{ __('devboard::t.save') }}" aria-label="{{ __('devboard::t.save') }}">✔</button>
    <button type="button" wire:click="cancelEdit" class="{{ $cancelClass }}" title="{{ __('devboard::t.cancel') }}" aria-label="{{ __('devboard::t.cancel') }}">✕</button>
</form>
