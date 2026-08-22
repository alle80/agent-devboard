<div class="mx-auto w-full max-w-5xl px-4 pt-24 pb-16 xl:px-8" style="{{ $skin['vars'] }}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="{{ $skin['h1'] }} inline-flex items-center gap-2"><x-griglia::icon name="ruler" /> {{ __('griglia::t.plan.index_title') }}</h1><p class="{{ $skin['sub'] }} mt-1">{{ __('griglia::t.plan.index_intro') }}</p></div>
        <div class="flex flex-wrap items-center gap-2"><a href="{{ route('griglia.plans.create') }}" class="tl-btn inline-flex items-center gap-1"><x-griglia::icon name="plus" /> {{ __('griglia::t.plan.new') }}</a><a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a></div>
    </div>
    @forelse ($plans as $plan)
        @php($percent = $plan->todos_count > 0 ? (int) round($plan->done_count / $plan->todos_count * 100) : 0)
        <section wire:key="plan-{{ $plan->id }}" class="{{ $skin['card'] }} mb-4" aria-labelledby="plan-{{ $plan->id }}-title">
            <div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0 flex-1"><h2 id="plan-{{ $plan->id }}-title" class="{{ $skin['h2'] }} truncate">{{ $plan->name }}</h2><p class="{{ $skin['help'] }} mt-1 line-clamp-2 text-sm">{{ $plan->plan_prompt }}</p></div><span class="{{ $skin['label'] }} shrink-0 text-sm tabular-nums">{{ __('griglia::t.plan.progress', ['done' => $plan->done_count, 'total' => $plan->todos_count]) }}</span></div>
            <div class="tl-meter mt-3 block" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"><span style="width: {{ $percent }}%"></span></div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" wire:click="open({{ $plan->id }})" class="tl-btn tl-btn-sm inline-flex items-center gap-1"><x-griglia::icon name="list" /> {{ __('griglia::t.plan.open') }}</button>
                <a href="{{ route('griglia.plans.edit', ['list' => $plan->id]) }}" class="tl-btn tl-btn-sm inline-flex items-center gap-1"><x-griglia::icon name="edit" /> {{ __('griglia::t.plan.edit_title') }}</a>
                @if ($plan->running_count > 0 && ! $plan->plan_paused)
                    <button type="button" wire:click="pause({{ $plan->id }})" class="tl-btn tl-btn-sm inline-flex items-center gap-1"><x-griglia::icon name="pause" /> {{ __('griglia::t.plan.pause') }}</button><span class="db-badge db-badge-working inline-flex items-center gap-1 text-xs"><x-griglia::icon name="working" /> {{ __('griglia::t.plan.running') }}</span>
                @elseif ($plan->done_count < $plan->todos_count)
                    <button type="button" wire:click="start({{ $plan->id }})" class="tl-btn tl-btn-sm inline-flex items-center gap-1"><x-griglia::icon name="play" /> {{ $plan->done_count > 0 || $plan->plan_paused ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}</button>@if ($plan->plan_paused)<span class="{{ $skin['help'] }} text-xs">{{ __('griglia::t.plan.paused_label') }}</span>@endif
                @else
                    <span class="{{ $skin['help'] }} inline-flex items-center gap-1 text-xs"><x-griglia::icon name="done" /> {{ __('griglia::t.plan.completed') }}</span>
                @endif
            </div>
        </section>
    @empty
        <div class="{{ $skin['card'] }} text-center"><p class="{{ $skin['label'] }}">{{ __('griglia::t.plan.empty_title') }}</p><p class="{{ $skin['help'] }} mt-1 text-sm">{{ __('griglia::t.plan.empty_help') }}</p></div>
    @endforelse
</div>
