{{--
    Sezione nota del modale, condivisa da tutti gli stili.
    Variabili attese: $todo, $notesDraft, $readonly (elemento completato: niente modifica), più le classi di stile:
      $boxClass   contenitore della nota
      $labelClass etichetta ("Nota")
      $textClass  testo della nota
      $inputClass textarea in modifica
      $cancelClass bottone del passo indietro (niente più salva/annulla, task 438)
      $label      testo etichetta (default "Nota")
--}}
@php($label = $label ?? __('griglia::t.note'))
<div class="{{ $boxClass }}">
    @if ($readonly)
        {{-- Elemento completato: la nota si legge soltanto --}}
        <span class="{{ $labelClass }}">{{ $label }}</span>
        @if ($todo->notes)
            <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Griglia\Support\Markdown::render($todo->notes) !!}</div>
        @else
            <p class="{{ $textClass }} italic opacity-50">{{ __('griglia::t.note_empty_ro') }}</p>
        @endif
    @elseif ($notesDraft !== null)
        <form
            wire:submit="finishNotes"
            x-data="{}"
            x-on:click.outside="$wire.set('notesDraft', $el.querySelector('textarea').value); $wire.finishNotes()"
            class="space-y-2"
        >
            <span class="{{ $labelClass }}">{{ $label }}</span>
            <x-griglia::md-editor
                model="notesDraft"
                :live="true"
                :rows="4"
                :placeholder="__('griglia::t.note_placeholder')"
                :inputClass="$inputClass"
                wire:keydown.escape="finishNotes"
            />
            <p class="text-xs opacity-60">{{ __('griglia::t.md_hint') }} {{ __('griglia::t.autosave_hint') }}</p>
            <div class="flex items-center justify-end gap-2">
                @if ($notesOriginal !== null && trim($notesDraft) !== $notesOriginal)
                    <button type="button" wire:click="revertNotes" class="{{ $cancelClass }} inline-flex items-center gap-1" title="{{ __('griglia::t.revert') }}"><x-griglia::icon name="undo" /> {{ __('griglia::t.revert') }}</button>
                @endif
            </div>
        </form>
    @else
        <div class="flex items-start justify-between gap-3">
            {{-- Tap/click sulla nota (o sul segnaposto) apre la modifica --}}
            <button
                type="button"
                wire:click="editNotes"
                title="{{ __('griglia::t.note_tap') }}"
                class="min-w-0 flex-1 cursor-text text-left"
             aria-label="{{ __('griglia::t.note_tap') }}">
                <span class="{{ $labelClass }}">{{ $label }}</span>
                @if ($todo->notes)
                    {{-- whitespace-pre-wrap + break-words: la nota si legge tutta, a capo compresi --}}
                    <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Griglia\Support\Markdown::render($todo->notes) !!}</div>
                @else
                    <p class="{{ $textClass }} italic opacity-50">{{ __('griglia::t.note_empty') }}</p>
                @endif
            </button>
            <button
                type="button"
                wire:click="editNotes"
                title="{{ __('griglia::t.note_edit') }}"
                class="shrink-0 cursor-pointer text-lg opacity-40 transition hover:scale-110 hover:opacity-100"
             aria-label="{{ __('griglia::t.note_edit') }}"><x-griglia::icon name="edit" /></button>
        </div>
    @endif

    {{-- Commento dell'assistente (risposta a una richiesta): sola lettura, distinto dalla nota --}}
    @if ($todo->claude_comment)
        <div class="mt-3 border-t-2 border-dashed border-current/30 pt-2">
            <span class="{{ $labelClass }} inline-flex items-center gap-1"><x-griglia::icon name="bot" /> {{ __('griglia::t.agent_box', ['agent' => \Alle80\Griglia\Agent::name()]) }}</span>
            <div class="{{ $textClass }} db-prose break-words text-[0.95em] opacity-90">{!! \Alle80\Griglia\Support\Markdown::render($todo->claude_comment) !!}</div>
        </div>
    @endif

    {{-- Statistiche del task: tempo di lavoro dell'agente (intervalli 🔧) + token riportati --}}
    @if ($todo->hasStats())
        <dl class="db-stats mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t-2 border-dashed border-current/30 pt-2 text-xs opacity-80" title="{{ __('griglia::t.stats_hint') }}">
            <dt class="{{ $labelClass }} inline-flex items-center gap-1 text-xs"><x-griglia::icon name="chart" /> {{ __('griglia::t.stats') }}</dt>
            @if ($todo->workSeconds() > 0)
                <dd class="tabular-nums" title="{{ __('griglia::t.stats_time') }}"><x-griglia::icon name="clock" /> <span class="sr-only">{{ __('griglia::t.stats_time') }}:</span> {{ \Alle80\Griglia\Models\Todo::formatDuration($todo->workSeconds()) }}@if ($todo->working) <span class="db-stats-live" aria-label="live">…</span>@endif</dd>
            @endif
            @if ($todo->tokens_in > 0 || $todo->tokens_out > 0)
                <dd class="tabular-nums" title="{{ __('griglia::t.stats_tokens') }}"><x-griglia::icon name="coins" /> <span class="sr-only">{{ __('griglia::t.stats_tokens') }}:</span> {{ \Alle80\Griglia\Models\Todo::formatTokens((int) $todo->tokens_in) }} in / {{ \Alle80\Griglia\Models\Todo::formatTokens((int) $todo->tokens_out) }} out</dd>
            @endif
        </dl>
    @endif
</div>
