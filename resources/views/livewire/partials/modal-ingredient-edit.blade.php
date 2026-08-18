{{--
    Form inline di rinomina di un sotto-task (sostituisce la riga mentre è in modifica).
    Variabili attese: $inputClass, $okClass, $cancelClass
--}}
<form wire:submit="saveIngredient" class="flex min-w-0 flex-1 items-center gap-2">
    <input
        type="text"
        wire:model="ingredientDraft"
        wire:keydown.escape="cancelEditIngredient"
        x-init="$el.focus(); $el.select()"
        class="{{ $inputClass }} w-full min-w-0 flex-1"
    >
    <button type="submit" class="{{ $okClass }}" title="{{ __('devboard::t.save') }}" aria-label="{{ __('devboard::t.save') }}">✔</button>
    <button type="button" wire:click="cancelEditIngredient" class="{{ $cancelClass }}" title="{{ __('devboard::t.cancel') }}" aria-label="{{ __('devboard::t.cancel') }}">✕</button>
</form>
