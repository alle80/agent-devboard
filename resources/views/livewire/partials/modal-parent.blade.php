{{--
    Contesto ereditato: questo todo è stato «ripreso» da uno chiuso. Mostra, richiudibile,
    la nota, il commento di Claude e i sotto-task dell'originale (sola lettura).
    Variabili attese: $todo (con relazione parent), più le classi $boxClass/$labelClass/$textClass.
--}}
@if ($todo->parent)
    <details class="{{ $boxClass }} modal-parent group" open>
        <summary class="cursor-pointer list-none">
            <span class="{{ $labelClass }}">{{ __('devboard::t.resumes', ['title' => $todo->parent->title]) }}</span>
            <span class="ml-1 text-xs opacity-60">{{ __('devboard::t.resumes_hint') }}</span>
        </summary>
        <div class="mt-2 space-y-2 text-[0.95em]">
            @if ($todo->parent->notes)
                <p class="{{ $textClass }} whitespace-pre-wrap break-words">{{ $todo->parent->notes }}</p>
            @endif
            @if ($todo->parent->claude_comment)
                <p class="{{ $textClass }} whitespace-pre-wrap break-words opacity-80"><span class="font-bold"><x-devboard::icon name="bot" /> {{ __('devboard::t.agent_box', ['agent' => \Alle80\Devboard\Agent::name()]) }}:</span> {{ $todo->parent->claude_comment }}</p>
            @endif
            @if ($todo->parent->ingredients->isNotEmpty())
                <ul class="{{ $textClass }} list-inside space-y-0.5 opacity-80">
                    @foreach ($todo->parent->ingredients as $i)
                        <li class="flex items-start gap-1"><x-devboard::icon :name="$i->checked ? 'done' : 'waiting'" class="mt-0.5" /> <span>{{ $i->name }}</span></li>
                    @endforeach
                </ul>
            @endif
            @if (! $todo->parent->notes && ! $todo->parent->claude_comment && $todo->parent->ingredients->isEmpty())
                <p class="{{ $textClass }} italic opacity-50">{{ __('devboard::t.parent_empty') }}</p>
            @endif
        </div>
    </details>
@endif
