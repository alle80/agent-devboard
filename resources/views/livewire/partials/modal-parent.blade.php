{{--
    Contesto ereditato: questo todo è stato «ripreso» da uno chiuso. Mostra, richiudibile,
    la nota, il commento di Claude e i sotto-task dell'originale (sola lettura).
    Variabili attese: $todo (con relazione parent), più le classi $boxClass/$labelClass/$textClass.
--}}
@if ($todo->parent)
    <details class="{{ $boxClass }} modal-parent group" open>
        <summary class="cursor-pointer list-none">
            <span class="{{ $labelClass }}">{{ __('griglia::t.resumes', ['title' => $todo->parent->title]) }}</span>
            <span class="ml-1 text-xs opacity-60">{{ __('griglia::t.resumes_hint') }}</span>
        </summary>
        <div class="mt-2 space-y-2 text-[0.95em]">
            @if ($todo->parent->notes)
                <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Griglia\Support\Markdown::render($todo->parent->notes) !!}</div>
            @endif
            @if ($todo->parent->claude_comment)
                <div class="{{ $textClass }} break-words opacity-80"><span class="font-bold"><x-griglia::icon name="bot" /> {{ __('griglia::t.agent_box', ['agent' => \Alle80\Griglia\Agent::name()]) }}:</span>
                    <div class="db-prose">{!! \Alle80\Griglia\Support\Markdown::render($todo->parent->claude_comment) !!}</div>
                </div>
            @endif
            @if ($todo->parent->ingredients->isNotEmpty())
                <ul class="{{ $textClass }} list-inside space-y-0.5 opacity-80">
                    @foreach ($todo->parent->ingredients as $i)
                        <li class="flex items-start gap-1"><x-griglia::icon :name="$i->checked ? 'done' : 'waiting'" class="mt-0.5" /> <span>{{ $i->name }}</span></li>
                    @endforeach
                </ul>
            @endif
            @if (! $todo->parent->notes && ! $todo->parent->claude_comment && $todo->parent->ingredients->isEmpty())
                <p class="{{ $textClass }} italic opacity-50">{{ __('griglia::t.parent_empty') }}</p>
            @endif
        </div>
    </details>
@endif
