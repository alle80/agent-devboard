@php
    $executor = \Alle80\Griglia\Agent::effective($todo);
    $latestAttempt = $todo->reviewAttempts->last();
@endphp
@if (\Alle80\Griglia\Agent::many() || $todo->isReviewAttempt() || $todo->review_status)
<section class="tl-card px-4 py-3" data-review-panel>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="tl-display tl-accent inline-flex items-center gap-1 text-sm"><x-griglia::icon name="check" /> {{ __('griglia::t.review.title') }}</h3>
        @if ($todo->isReviewAttempt())
            <span class="db-badge db-badge-working text-xs">{{ __('griglia::t.review.round', ['round' => $todo->review_round]) }}</span>
        @elseif ($todo->review_status)
            @php($status = $todo->review_status->value)
            <span class="db-badge db-badge-{{ $status === 'approved' ? 'done' : ($status === 'changes_requested' ? 'question' : 'working') }} text-xs">{{ __('griglia::t.review.status.'.$status) }}</span>
        @elseif ($todo->reviewer_agent)
            <span class="db-badge db-badge-waiting text-xs">{{ __('griglia::t.review.status.assigned') }}</span>
        @endif
    </div>
    @if ($todo->isReviewAttempt())
        <p class="mt-2 text-sm">{{ __('griglia::t.review.owned_by', ['agent' => \Alle80\Griglia\Agent::label($todo->agent)]) }}</p>
        @if ($todo->reviewOf)<button type="button" wire:click="openFor({{ $todo->reviewOf->id }})" class="mt-1 inline-flex items-center gap-1 text-xs underline opacity-80"><x-griglia::icon name="link" /> {{ __('griglia::t.review.original', ['title' => $todo->reviewOf->title]) }}</button>@endif
    @else
        <label for="task-reviewer-{{ $todo->id }}" class="mt-2 block text-xs font-semibold">{{ __('griglia::t.review.reviewer') }}</label>
        @if (! $todo->working && ! $todo->completed && ! $todo->review_status)
            <select id="task-reviewer-{{ $todo->id }}" wire:change="setReviewer($event.target.value)" class="tl-input mt-1 w-full px-3 py-2 text-sm">
                <option value="">{{ __('griglia::t.review.none') }}</option>
                @foreach (\Alle80\Griglia\Agent::all() as $key => $label) @continue($key === $executor) <option value="{{ $key }}" @selected($todo->reviewer_agent === $key)>{{ $label }}</option> @endforeach
            </select>
            <p class="mt-1 text-xs opacity-60">{{ __('griglia::t.review.help') }}</p>
        @else
            <p class="mt-1 text-sm {{ $todo->reviewer_agent ? '' : 'opacity-60' }}">{{ $todo->reviewer_agent ? \Alle80\Griglia\Agent::label($todo->reviewer_agent) : __('griglia::t.review.none') }}</p>
        @endif
        @if ($latestAttempt)<button type="button" wire:click="openFor({{ $latestAttempt->id }})" class="mt-2 inline-flex items-center gap-1 text-xs underline opacity-80"><x-griglia::icon name="link" /> {{ __('griglia::t.review.open_attempt', ['round' => $latestAttempt->review_round]) }}</button>@endif
    @endif
</section>
@endif
