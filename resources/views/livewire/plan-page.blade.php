{{-- Pagina dedicata alla creazione di un piano: spazio vero per scrivere l'obiettivo (task 342). --}}
<div class="mx-auto w-full max-w-xl px-4 pt-24 pb-16 lg:max-w-3xl" style="{{ $skin['vars'] }}">
    <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} inline-flex items-center gap-2"><x-griglia::icon name="ruler" size="1em" /> {{ __('griglia::t.plan.page_title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>

    <form wire:submit="create" class="{{ $skin['card'] }}">
        <h2 class="{{ $skin['h2'] }}">{{ __('griglia::t.plan.goal_label') }}</h2>
        <p class="{{ $skin['sub'] }} mb-3">{{ $aiAvailable ? __('griglia::t.plan.hint') : __('griglia::t.plan.hint_no_ai') }}</p>

        <textarea
            id="plan-goal"
            wire:model="prompt"
            rows="12"
            autofocus
            x-on:keydown.ctrl.enter="$wire.create()"
            x-on:keydown.meta.enter="$wire.create()"
            placeholder="{{ __('griglia::t.plan.prompt_placeholder') }}"
            class="db-plan-goal {{ $skin['input'] }} min-h-[14rem] w-full resize-y leading-relaxed"
        ></textarea>
        @error('prompt')<p class="db-setting-warn mt-2">{{ $message }}</p>@enderror

        <div class="mt-5">
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

        <div class="mt-6 flex flex-wrap items-center gap-2">
            <button type="submit" class="{{ $skin['back'] }} inline-flex items-center gap-1" wire:loading.attr="disabled" wire:target="create" x-bind:aria-busy="$wire.__instance?.loading ? 'true' : 'false'">
                <x-griglia::icon name="check" />
                <span wire:loading.remove wire:target="create">{{ __('griglia::t.plan.build') }}</span>
                <span wire:loading wire:target="create">{{ __('griglia::t.plan.building') }}</span>
            </button>
            <a href="{{ $skin['home'] }}" class="{{ $skin['help'] }} px-2 py-1.5 hover:underline">{{ __('griglia::t.cancel') }}</a>
        </div>
    </form>
</div>
