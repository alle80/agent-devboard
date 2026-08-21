{{--
    Form inline di rinomina di un todo (sostituisce il titolo mentre è in modifica).
    Variabili attese: $inputClass, $okClass, $cancelClass
--}}
<form wire:submit="saveEdit" x-data="{ len: 0 }" x-init="len = $el.querySelector('input').value.length" class="flex min-w-0 flex-1 items-center gap-2">
    <input
        type="text"
        wire:model.live.debounce.600ms="titleDraft"
        x-on:blur="$wire.set('titleDraft', $event.target.value)"
        maxlength="{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}"
        x-on:input="len = $event.target.value.length"
        wire:keydown.escape="cancelEdit"
        x-init="$el.focus(); $el.select()"
        class="{{ $inputClass }} w-full min-w-0 flex-1"
    >
    <span class="shrink-0 text-xs tabular-nums opacity-60" x-text="len + '/{{ \Alle80\Griglia\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
    <x-griglia::autosaved />
    <button type="submit" class="{{ $okClass }}" title="{{ __('griglia::t.save') }}" aria-label="{{ __('griglia::t.save') }}"><x-griglia::icon name="check" :stroke="2.5" /></button>
    <button type="button" wire:click="cancelEdit" class="{{ $cancelClass }}" title="{{ __('griglia::t.cancel') }}" aria-label="{{ __('griglia::t.cancel') }}"><x-griglia::icon name="close" /></button>
</form>
