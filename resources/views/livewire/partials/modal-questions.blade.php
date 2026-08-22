{{--
    Domande dell'assistente sull'elemento, con risposte dell'utente e passo finale di riavvio.
    Variabili attese: $todo, $answers, $readonly (elemento completato: risposte in sola lettura), più le classi di stile:
      $boxClass    contenitore
      $labelClass  intestazione
      $textClass   testo domanda
      $inputClass  textarea risposta
      $okClass     bottone salva / riparti
--}}
@if ($todo->questions->isNotEmpty())
    @php($unanswered = $todo->questions->whereNull('answer')->count())
    <div class="{{ $boxClass }}">
        <div class="mb-2 flex items-center justify-between gap-2">
            <span class="{{ $labelClass }} inline-flex items-center gap-1"><span class="db-badge db-badge-question"><x-griglia::icon name="question" :stroke="2" /></span> {{ __('griglia::t.questions_title', ['agent' => \Alle80\Griglia\Agent::name()]) }}</span>
            <span class="text-xs opacity-70">{{ __('griglia::t.answers_count', ['answered' => $todo->questions->count() - $unanswered, 'total' => $todo->questions->count()]) }}</span>
        </div>

        <ol class="space-y-3">
            @foreach ($todo->questions as $q)
                <li wire:key="q-{{ $q->id }}">
                    <div class="{{ $textClass }} mb-1 break-words"><span class="mr-1 opacity-60">{{ $loop->iteration }}.</span><div class="db-prose inline-block align-top" style="max-width: calc(100% - 1.5rem);">{!! \Alle80\Griglia\Support\Markdown::render($q->question) !!}</div></div>
                    @if ($readonly)
                        @if ($q->answer)
                            <div class="{{ $textClass }} db-prose break-words pl-4 text-[0.95em] opacity-80">{!! \Alle80\Griglia\Support\Markdown::render($q->answer) !!}</div>
                        @else
                            <p class="{{ $textClass }} break-words pl-4 text-[0.95em] opacity-80">{{ __('griglia::t.no_answer') }}</p>
                        @endif
                    @else
                    @if ($q->choices)
                        <div class="mb-2 flex flex-wrap gap-2 pl-4" role="group" aria-label="{{ __('griglia::t.closed_choices', ['n' => $loop->iteration]) }}">
                            @foreach ($q->choices as $choice)
                                <button type="button" wire:click="selectAnswer({{ $q->id }}, @js($choice))" class="{{ $okClass }} text-sm" @if (($answers[$q->id] ?? '') === $choice) aria-pressed="true" @endif>{{ $choice }}</button>
                            @endforeach
                        </div>
                    @endif
                    <form wire:submit="saveAnswer({{ $q->id }})" class="flex items-start gap-2">
                        <textarea
                            wire:model="answers.{{ $q->id }}"
                            rows="2"
                            placeholder="{{ __('griglia::t.your_answer') }}"
                            aria-label="{{ __('griglia::t.answer_label', ['n' => $loop->iteration]) }}"
                            class="{{ $inputClass }} block w-full min-w-0 flex-1 resize-y text-base"
                        ></textarea>
                        <x-griglia::mic target="textarea" class="shrink-0" />
                        <button type="submit" class="{{ $okClass }} shrink-0" title="{{ __('griglia::t.save_answer') }}" aria-label="{{ __('griglia::t.save_answer') }}">
                            @if ($q->answer)<x-griglia::icon name="check" :stroke="2.5" />@else{{ __('griglia::t.save') }}@endif
                        </button>
                    </form>
                    @endif
                </li>
            @endforeach
        </ol>

        {{-- Ultimo passo: rimettere in esecuzione --}}
        @if ($todo->question && ! $readonly)
            <div class="mt-4 border-t-2 border-dashed border-current/30 pt-3">
                @if ($unanswered === 0)
                    <p class="{{ $textClass }} mb-2 font-bold">{{ __('griglia::t.all_answered') }}</p>
                    <button type="button" wire:click="resumeWork" class="{{ $okClass }}">{{ __('griglia::t.restart') }}</button>
                @else
                    <p class="{{ $textClass }} text-sm opacity-70">{{ __('griglia::t.answer_all_first') }}</p>
                @endif
            </div>
        @endif
    </div>
@endif
