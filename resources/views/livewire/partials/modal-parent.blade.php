{{--
    Contesto ereditato: questo todo è stato «ripreso» da uno chiuso. Mostra la nota, il commento
    dell'agente e i sotto-task dell'originale (sola lettura), **chiuso**: è contesto, non la richiesta
    di adesso — si apre quando serve (task 363). Un resume può nascere da un altro resume: si mostra
    tutta la catena, dal più recente al più vecchio (task 416).
    Variabili attese: $todo (con relazione parent), più le classi $boxClass/$labelClass/$textClass.
--}}
@php($chain = $todo->resumeChain())
@if ($chain->isNotEmpty())
    <details class="{{ $boxClass }} modal-parent group" x-data="{ o: false }" x-on:toggle="o = $el.open">
        <summary class="flex cursor-pointer list-none items-start gap-1.5">
            <x-griglia::icon name="chevron" class="mt-1 shrink-0 transition-transform" x-bind:class="o ? 'rotate-180' : ''" />
            <span class="min-w-0">
                <span class="{{ $labelClass }}">{{ __('griglia::t.resumes', ['title' => $chain->first()->title]) }}</span>
                @if ($chain->count() > 1)
                    <span class="ml-1 text-xs tabular-nums opacity-70">{{ __('griglia::t.resumes_more', ['count' => $chain->count() - 1]) }}</span>
                @endif
                <span class="ml-1 text-xs opacity-60">{{ __('griglia::t.resumes_hint') }}</span>
            </span>
        </summary>
        <div class="mt-2 space-y-2 text-[0.95em]">
            @foreach ($chain as $step => $previous)
                @if ($step > 0)
                    <div class="{{ $labelClass }} mt-3 border-t pt-2 text-xs opacity-70">{{ __('griglia::t.resumes_step', ['title' => $previous->title]) }}</div>
                @endif
                @if ($previous->notes)
                    <div class="{{ $textClass }} db-prose break-words">{!! \Alle80\Griglia\Support\Markdown::render($previous->notes) !!}</div>
                @endif
                @if ($previous->claude_comment)
                    <div class="{{ $textClass }} break-words opacity-80"><span class="font-bold"><x-griglia::icon name="bot" /> {{ __('griglia::t.agent_box', ['agent' => \Alle80\Griglia\Agent::name()]) }}:</span>
                        <div class="db-prose">{!! \Alle80\Griglia\Support\Markdown::renderAgentResponse($previous->claude_comment) !!}</div>
                    </div>
                @endif
                @if ($previous->ingredients->isNotEmpty())
                    <ul class="{{ $textClass }} list-inside space-y-0.5 opacity-80">
                        @foreach ($previous->ingredients as $i)
                            <li class="flex items-start gap-1"><x-griglia::icon :name="$i->checked ? 'done' : 'waiting'" class="mt-0.5" /> <span>{{ $i->name }}</span></li>
                        @endforeach
                    </ul>
                @endif
                @if (! $previous->notes && ! $previous->claude_comment && $previous->ingredients->isEmpty())
                    <p class="{{ $textClass }} italic opacity-50">{{ __('griglia::t.parent_empty') }}</p>
                @endif
            @endforeach
        </div>
    </details>
@endif
