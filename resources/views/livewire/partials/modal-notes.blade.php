{{--
    Sezione nota del modale, condivisa da tutti gli stili.
    Variabili attese: $todo, $notesDraft, $readonly (elemento completato: niente modifica), più le classi di stile:
      $boxClass   contenitore della nota
      $labelClass etichetta ("Nota")
      $textClass  testo della nota
      $inputClass textarea in modifica
      $okClass    bottone salva
      $cancelClass bottone annulla
      $label      testo etichetta (default "Nota")
--}}
@php($label = $label ?? __('devboard::t.note'))
<div class="{{ $boxClass }}">
    @if ($readonly)
        {{-- Elemento completato: la nota si legge soltanto --}}
        <span class="{{ $labelClass }}">{{ $label }}</span>
        @if ($todo->notes)
            <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Devboard\Support\Markdown::render($todo->notes) !!}</div>
        @else
            <p class="{{ $textClass }} italic opacity-50">{{ __('devboard::t.note_empty_ro') }}</p>
        @endif
    @elseif ($notesDraft !== null)
        <form wire:submit="saveNotes" class="space-y-2">
            <span class="{{ $labelClass }}">{{ $label }}</span>
            <x-devboard::md-editor
                model="notesDraft"
                :rows="4"
                :placeholder="__('devboard::t.note_placeholder')"
                :inputClass="$inputClass"
                wire:keydown.escape="cancelNotes"
            />
            <p class="text-xs opacity-60">{{ __('devboard::t.md_hint') }}</p>
            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="cancelNotes" class="{{ $cancelClass }}">{{ __('devboard::t.cancel') }}</button>
                <button type="submit" class="{{ $okClass }}">{{ __('devboard::t.save') }}</button>
            </div>
        </form>
    @else
        <div class="flex items-start justify-between gap-3">
            {{-- Tap/click sulla nota (o sul segnaposto) apre la modifica --}}
            <button
                type="button"
                wire:click="editNotes"
                title="{{ __('devboard::t.note_tap') }}"
                class="min-w-0 flex-1 cursor-text text-left"
             aria-label="{{ __('devboard::t.note_tap') }}">
                <span class="{{ $labelClass }}">{{ $label }}</span>
                @if ($todo->notes)
                    {{-- whitespace-pre-wrap + break-words: la nota si legge tutta, a capo compresi --}}
                    <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Devboard\Support\Markdown::render($todo->notes) !!}</div>
                @else
                    <p class="{{ $textClass }} italic opacity-50">{{ __('devboard::t.note_empty') }}</p>
                @endif
            </button>
            <button
                type="button"
                wire:click="editNotes"
                title="{{ __('devboard::t.note_edit') }}"
                class="shrink-0 cursor-pointer text-lg opacity-40 transition hover:scale-110 hover:opacity-100"
             aria-label="{{ __('devboard::t.note_edit') }}"><x-devboard::icon name="edit" /></button>
        </div>
    @endif

    {{-- Commento dell'assistente (risposta a una richiesta): sola lettura, distinto dalla nota --}}
    @if ($todo->claude_comment)
        <div class="mt-3 border-t-2 border-dashed border-current/30 pt-2">
            <span class="{{ $labelClass }} inline-flex items-center gap-1"><x-devboard::icon name="bot" /> {{ __('devboard::t.agent_box', ['agent' => \Alle80\Devboard\Agent::name()]) }}</span>
            <div class="{{ $textClass }} db-prose break-words text-[0.95em] opacity-90">{!! \Alle80\Devboard\Support\Markdown::render($todo->claude_comment) !!}</div>
        </div>
    @endif

    {{-- Statistiche del task: tempo di lavoro dell'agente (intervalli 🔧) + token riportati --}}
    @if ($todo->hasStats())
        <dl class="db-stats mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t-2 border-dashed border-current/30 pt-2 text-xs opacity-80" title="{{ __('devboard::t.stats_hint') }}">
            <dt class="{{ $labelClass }} inline-flex items-center gap-1 text-xs"><x-devboard::icon name="chart" /> {{ __('devboard::t.stats') }}</dt>
            @if ($todo->workSeconds() > 0)
                <dd class="tabular-nums" title="{{ __('devboard::t.stats_time') }}"><x-devboard::icon name="clock" /> <span class="sr-only">{{ __('devboard::t.stats_time') }}:</span> {{ \Alle80\Devboard\Models\Todo::formatDuration($todo->workSeconds()) }}@if ($todo->working) <span class="db-stats-live" aria-label="live">…</span>@endif</dd>
            @endif
            @if ($todo->tokens_in > 0 || $todo->tokens_out > 0)
                <dd class="tabular-nums" title="{{ __('devboard::t.stats_tokens') }}"><x-devboard::icon name="coins" /> <span class="sr-only">{{ __('devboard::t.stats_tokens') }}:</span> {{ \Alle80\Devboard\Models\Todo::formatTokens((int) $todo->tokens_in) }} in / {{ \Alle80\Devboard\Models\Todo::formatTokens((int) $todo->tokens_out) }} out</dd>
            @endif
        </dl>
    @endif
</div>
