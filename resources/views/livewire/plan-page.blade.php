{{-- Pagina dedicata alla creazione di un piano: spazio vero per scrivere l'obiettivo (task 342). --}}
<div class="mx-auto w-full max-w-xl px-4 pt-24 pb-16 lg:max-w-3xl" style="{{ $skin['vars'] }}"
     x-data="{ dirty: false }"
     x-on:keydown.escape.window="$wire.prompt.trim() === '' || confirm(@js(__('griglia::t.plan.leave_confirm'))) ? $wire.cancel() : null">
    <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} inline-flex items-center gap-2"><x-griglia::icon name="ruler" size="1em" /> {{ $list ? __('griglia::t.plan.edit_title') : __('griglia::t.plan.page_title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>

    <form wire:submit="{{ $list ? 'saveGoal' : 'create' }}" class="{{ $skin['card'] }}">
        <h2 class="{{ $skin['h2'] }}">{{ __('griglia::t.plan.goal_label') }}</h2>
        <p class="{{ $skin['sub'] }} mb-3">{{ $aiAvailable ? __('griglia::t.plan.hint') : __('griglia::t.plan.hint_no_ai') }}</p>

        <div class="flex items-start gap-2">
            <textarea
                id="plan-goal"
                wire:model.live.debounce.400ms="prompt"
                rows="12"
                autofocus
                x-on:keydown.ctrl.enter="$wire.create()"
                x-on:keydown.meta.enter="$wire.create()"
                placeholder="{{ __('griglia::t.plan.prompt_placeholder') }}"
                class="db-plan-goal {{ $skin['input'] }} min-h-[14rem] w-full flex-1 resize-y leading-relaxed"
            ></textarea>
            <x-griglia::mic class="tl-btn tl-btn-icon shrink-0" within="form" target=".db-plan-goal" />
        </div>
        <p class="{{ $skin['help'] }} mt-1 text-right tabular-nums">{{ mb_strlen(trim($prompt)) }}</p>
        @error('prompt')<p class="db-setting-warn mt-2">{{ $message }}</p>@enderror

        <div class="mt-6 border-t border-current/15 pt-4">
            <label for="plan-name" class="{{ $skin['label'] }}">{{ __('griglia::t.plan.name_label') }}</label>
            <p class="{{ $skin['help'] }} mb-1">{{ __('griglia::t.plan.name_help') }}</p>
            <input
                id="plan-name"
                type="text"
                wire:model="name"
                maxlength="60"
                autocomplete="off"
                class="{{ $skin['input'] }} w-full"
            >
        </div>

        @if (count($agents) > 1)
            <div class="mt-4">
                <label for="plan-agent" class="{{ $skin['label'] }}">{{ __('griglia::t.agent_of_list') }}</label>
                <p class="{{ $skin['help'] }} mb-1">{{ __('griglia::t.plan.agent_help') }}</p>
                <select id="plan-agent" wire:model="agent" class="setting-input {{ $skin['input'] }} w-full sm:w-auto sm:min-w-[14rem]">
                    <option value="">{{ __('griglia::t.agent_default', ['agent' => \Alle80\Griglia\Agent::label(null)]) }}</option>
                    @foreach ($agents as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-2">
            @if ($list)
                <button type="submit" class="{{ $skin['back'] }} inline-flex items-center gap-1" wire:loading.attr="disabled" wire:target="saveGoal,rebuild">
                    <x-griglia::icon name="check" /> {{ __('griglia::t.plan.save_goal') }}
                </button>
                <button
                    type="button"
                    wire:click="rebuild"
                    @if ($untouchedCount > 0) wire:confirm="{{ __('griglia::t.plan.rebuild_confirm', ['count' => $untouchedCount]) }}" @endif
                    class="{{ $skin['back'] }} inline-flex items-center gap-1"
                    wire:loading.attr="disabled" wire:target="saveGoal,rebuild"
                >
                    <x-griglia::icon name="resume" />
                    <span wire:loading.remove wire:target="rebuild">{{ __('griglia::t.plan.rebuild') }}</span>
                    <span wire:loading wire:target="rebuild">{{ __('griglia::t.plan.building') }}</span>
                </button>
            @else
                <button type="submit" class="{{ $skin['back'] }} inline-flex items-center gap-1" wire:loading.attr="disabled" wire:target="create">
                    <x-griglia::icon name="check" />
                    <span wire:loading.remove wire:target="create">{{ __('griglia::t.plan.build') }}</span>
                    <span wire:loading wire:target="create">{{ __('griglia::t.plan.building') }}</span>
                </button>
            @endif
            <button
                type="button"
                wire:click="cancel"
                @if (! $list) x-on:click="if ($wire.prompt.trim() !== '' && ! confirm(@js(__('griglia::t.plan.leave_confirm')))) $event.stopImmediatePropagation()" @endif
                class="{{ $skin['help'] }} cursor-pointer px-2 py-1.5 hover:underline"
            >{{ __('griglia::t.cancel') }}</button>
        </div>
        @if ($list && $untouchedCount === 0)
            <p class="{{ $skin['help'] }} mt-2">{{ __('griglia::t.plan.rebuild_none') }}</p>
        @endif
    </form>
</div>
