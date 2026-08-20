{{--
    Form inline di rinomina di un sotto-task (sostituisce la riga mentre è in modifica).
    Variabili attese: $inputClass, $okClass, $cancelClass
--}}
<form wire:submit="saveIngredient" class="min-w-0 flex-1 space-y-2">
    <x-griglia::md-editor
        model="ingredientDraft"
        :rows="1"
        inputClass="{{ $inputClass }}"
        wire:keydown.escape="cancelEditIngredient"
    />
    <div class="flex items-center justify-end gap-2">
        <button type="button" wire:click="cancelEditIngredient" class="{{ $cancelClass }}" title="{{ __('griglia::t.cancel') }}" aria-label="{{ __('griglia::t.cancel') }}"><x-griglia::icon name="close" /></button>
        <button type="submit" class="{{ $okClass }}" title="{{ __('griglia::t.save') }}" aria-label="{{ __('griglia::t.save') }}"><x-griglia::icon name="check" :stroke="2.5" /></button>
    </div>
</form>
