{{--
    Avviso in cima al modale quando l'elemento è completato: note, domande e sotto-task
    si possono solo leggere. Per modificarli va riaperto (togliendo la spunta in lista).
    Da qui si può anche «riprendere» il task: nuovo elemento collegato con il contesto di questo.
    Variabili attese: $readonly (dal componente); opzionale $readonlyClass per lo stile.
--}}
@if ($readonly)
    <div class="{{ $readonlyClass ?? 'text-sm font-bold opacity-80' }} modal-readonly flex flex-wrap items-center gap-x-3 gap-y-2" role="status">
        <span class="flex items-center gap-2">
            <span aria-hidden="true">🔒</span>
            <span>{{ __('devboard::t.readonly_notice') }}</span>
        </span>
        <button
            type="button"
            wire:click="resumeTodo"
            class="cursor-pointer rounded border-2 border-current px-2 py-0.5 text-xs font-bold whitespace-nowrap transition hover:opacity-100"
            title="{{ __('devboard::t.resume_with_changes_hint') }}"
        >{{ __('devboard::t.resume_with_changes') }}</button>
    </div>
@endif
