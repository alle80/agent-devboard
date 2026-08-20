<div class="mx-auto w-full max-w-3xl px-4 pt-24 pb-16 sm:pt-24 lg:max-w-5xl xl:px-8" style="{{ $skin['vars'] }}" wire:poll.60s>
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} db-ctx-h1">{{ __('griglia::t.agents.title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>
    <p class="{{ $skin['sub'] }} mb-2">{{ __('griglia::t.agents.intro') }}</p>
    <p class="{{ $skin['help'] }} mb-4 text-xs">
        @if ($status['updated_at'])
            {{ __('griglia::t.agents.updated', ['when' => $status['updated_at']->diffForHumans()]) }}
            @if ($status['stale']) <span class="db-agent-stale">{{ __('griglia::t.agents.stale') }}</span>@endif
        @else
            {{ __('griglia::t.agents.never') }}
        @endif
    </p>

    @if (empty($status['agents']))
        {{-- Empty state: no snapshot yet / no agents configured --}}
        <div class="{{ $skin['card'] }} text-center">
            <p class="{{ $skin['label'] }}">{{ __('griglia::t.agents.empty_title') }}</p>
            <p class="{{ $skin['help'] }} mt-1 text-sm">{{ __('griglia::t.agents.empty_help') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($status['agents'] as $a)
                <section class="{{ $skin['card'] }} db-agent db-agent-{{ $a['level'] }}" aria-labelledby="agent-{{ $a['key'] }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="agent-{{ $a['key'] }}" class="{{ $skin['h2'] }} text-base">{{ $a['name'] }}</h2>
                        <span class="{{ $skin['help'] }} text-sm">{{ __('griglia::t.agents.plan') }}: <span class="{{ $skin['label'] }}">{{ $a['plan'] ?? '—' }}</span>@if ($a['plan_kind']) <span class="opacity-70">({{ $a['plan_kind'] }})</span>@endif</span>
                    </div>
                    @if ($a['error'])
                        {{-- Error state: the collector could not read this agent --}}
                        <p class="db-agent-error mt-2 text-sm">{{ __('griglia::t.agents.error') }}: {{ $a['error'] }}</p>
                    @elseif (empty($a['windows']))
                        <p class="{{ $skin['help'] }} mt-2 text-sm">{{ __('griglia::t.agents.not_configured') }}</p>
                    @else
                        <ul class="mt-3 space-y-3">
                            @foreach ($a['windows'] as $w)
                                <li wire:key="w-{{ $a['key'] }}-{{ $w['key'] }}">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm">
                                        <span class="{{ $skin['label'] }}">{{ $w['label'] }}</span>
                                        @if ($w['used'] === null)
                                            <span class="{{ $skin['help'] }}">{{ __('griglia::t.agents.na') }}</span>
                                        @else
                                            <span class="tabular-nums">
                                                <span class="db-agent-used">{{ __('griglia::t.agents.used', ['p' => $w['used']]) }}</span>
                                                <span class="{{ $skin['help'] }}"> · {{ __('griglia::t.agents.remaining', ['p' => $w['remaining']]) }}</span>
                                                @if ($w['level'] === 'over')<span class="db-agent-badge db-agent-badge-over ml-1">{{ __('griglia::t.agents.over') }}</span>
                                                @elseif ($w['level'] === 'critical')<span class="db-agent-badge db-agent-badge-critical ml-1">{{ __('griglia::t.agents.critical') }}</span>
                                                @elseif ($w['level'] === 'warn')<span class="db-agent-badge db-agent-badge-warn ml-1">{{ __('griglia::t.agents.warn') }}</span>@endif
                                            </span>
                                        @endif
                                    </div>
                                    <div class="db-agent-bar mt-1 h-2.5 w-full overflow-hidden rounded-full" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $w['bar'] }}" aria-label="{{ $w['label'] }}">
                                        <div class="db-agent-fill db-agent-fill-{{ $w['level'] }} h-full rounded-full" style="width: {{ $w['bar'] }}%"></div>
                                    </div>
                                    <div class="{{ $skin['help'] }} mt-0.5 flex flex-wrap justify-between gap-x-3 text-xs tabular-nums">
                                        <span>@if ($w['resets_in'] !== null){{ __('griglia::t.agents.resets_in', ['t' => \Alle80\Griglia\Support\AgentStatus::countdown($w['resets_in'])]) }} ({{ $w['resets']->timezone(config('app.timezone'))->format('d/m H:i') }})@endif</span>
                                        @if ($w['limit_dollars'] !== null)<span>{{ number_format($w['used_dollars'] ?? 0, 2) }} / {{ number_format($w['limit_dollars'], 2) }} $</span>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if (! empty($a['extra_usage']) && ! empty($a['extra_usage']['is_enabled']))
                            <p class="{{ $skin['help'] }} mt-2 text-xs">{{ __('griglia::t.agents.extra', ['used' => number_format((float) ($a['extra_usage']['used_credits'] ?? 0), 2), 'limit' => number_format((float) ($a['extra_usage']['monthly_limit'] ?? 0), 2)]) }}</p>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>
    @endif
</div>
