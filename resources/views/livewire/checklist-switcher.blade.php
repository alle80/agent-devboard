{{-- Selettore della lista corrente, identico su tutte le pagine --}}
<div class="fixed top-3 left-3 z-[60] flex items-start gap-2">
<details
    class="relative"
    style="font-family: system-ui, sans-serif"
    x-data="{ open: false }"
    x-bind:open="open"
    x-on:toggle="open = $el.open"
>
    <summary class="max-w-[48vw] cursor-pointer list-none truncate rounded-lg border-2 border-black bg-white px-2.5 py-1 text-xs font-bold sm:px-3 sm:py-1.5 sm:text-sm text-black shadow-[2px_2px_0_#000] select-none hover:bg-emerald-100 active:translate-y-px sm:max-w-xs [&::-webkit-details-marker]:hidden">
        <x-griglia::icon name="list" /> {{ $lists->firstWhere('id', $currentId)?->name ?? __('griglia::t.lists') }} <x-griglia::icon name="chevron" size=".9em" class="opacity-60" />
    </summary>
    <div class="absolute left-0 mt-1.5 max-h-[70vh] w-64 overflow-y-auto rounded-lg border-2 border-black bg-white p-1 text-black shadow-[3px_3px_0_#000]">

        @foreach ($lists as $list)
            <div wire:key="list-{{ $list->id }}" class="flex items-center gap-1 rounded {{ $list->id === $currentId ? 'bg-emerald-200' : 'hover:bg-emerald-100' }}">
                @if ($editingId === $list->id)
                    <form wire:submit="saveRename" class="flex min-w-0 flex-1 items-center gap-1 p-1">
                        <input
                            type="text"
                            wire:model="nameDraft"
                            wire:keydown.escape="cancelRename"
                            x-init="$el.focus(); $el.select()"
                            class="w-full min-w-0 flex-1 rounded border-2 border-black px-2 py-1 text-sm font-bold focus:bg-emerald-50 focus:outline-none"
                        >
                        <button type="submit" title="{{ __('griglia::t.save') }}" class="shrink-0 cursor-pointer rounded border-2 border-black bg-emerald-200 px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" aria-label="{{ __('griglia::t.save') }}"><x-griglia::icon name="check" :stroke="2.5" /></button>
                        <button type="button" wire:click="cancelRename" title="{{ __('griglia::t.cancel') }}" class="shrink-0 cursor-pointer rounded border-2 border-black bg-white px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" aria-label="{{ __('griglia::t.cancel') }}"><x-griglia::icon name="close" /></button>
                    </form>
                @else
                    <button
                        wire:click="switchTo({{ $list->id }})"
                        class="min-w-0 flex-1 cursor-pointer px-2 py-1.5 text-left text-sm font-bold"
                    >
                        <span class="block truncate">{{ $list->name }}@if ($list->id === $currentId) <x-griglia::icon name="check" size=".9em" />@endif</span>
                        <span class="block text-xs font-normal opacity-60">{{ $list->done_count }}/{{ $list->todos_count }} {{ __('griglia::t.done_short') }}</span>
                    </button>
                    @if (($list->chained_count > 0 || $list->plan_prompt) && $list->running_count > 0)
                        {{-- Plan running: the agent follows the chain --}}
                        <span class="db-badge db-badge-working shrink-0 px-1" title="{{ __('griglia::t.plan.running_short') }}" aria-label="{{ __('griglia::t.plan.running_short') }}"><x-griglia::icon name="working" :stroke="2" /></span>
                    @endif
                    @if (($list->chained_count > 0 || $list->plan_prompt) && $list->running_count === 0 && $list->done_count < $list->todos_count)
                        {{-- Plan list not running: start it from here --}}
                        <button
                            wire:click="startPlan({{ $list->id }})"
                            title="{{ $list->done_count > 0 ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}"
                            aria-label="{{ $list->done_count > 0 ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}"
                            class="shrink-0 cursor-pointer rounded border-2 border-black bg-emerald-200 px-1.5 py-1 text-sm shadow-[1px_1px_0_#000] active:translate-y-px"
                        ><x-griglia::icon name="play" /></button>
                    @endif
                    <button
                        wire:click="startRename({{ $list->id }})"
                        title="{{ __('griglia::t.rename_list') }}"
                        class="shrink-0 cursor-pointer px-1.5 py-1.5 text-sm opacity-30 hover:opacity-100"
                    ><x-griglia::icon name="edit" /></button>
                    @if ($lists->count() > 1)
                        <button
                            wire:click="deleteList({{ $list->id }})"
                            wire:confirm="{{ __('griglia::t.delete_list_confirm', ['name' => $list->name, 'count' => $list->todos_count]) }}"
                            title="{{ __('griglia::t.delete_list') }}"
                            class="shrink-0 cursor-pointer px-2 py-1.5 text-sm opacity-30 hover:text-red-600 hover:opacity-100"
                        ><x-griglia::icon name="close" /></button>
                    @endif
                @endif
            </div>
        @endforeach

        {{-- Nuova lista --}}
        <form wire:submit="create" class="mt-1 border-t-2 border-black/20 p-1 pt-2" x-data="{ plan: $wire.entangle('asPlan') }">
            <div class="flex items-center gap-1">
                <input
                    type="text"
                    wire:model="newName"
                    placeholder="{{ __('griglia::t.new_list') }}"
                    class="w-full min-w-0 flex-1 rounded border-2 border-black px-2 py-1 text-sm focus:bg-emerald-50 focus:outline-none"
                >
                <button type="submit" class="shrink-0 cursor-pointer rounded border-2 border-black bg-emerald-200 px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">+</span><span wire:loading wire:target="create">…</span>
                </button>
            </div>
            {{-- Plan mode: build the list from a prompt (chained tasks) --}}
            <label class="mt-1.5 flex cursor-pointer items-center gap-1.5 px-1 text-xs select-none">
                <input type="checkbox" x-model="plan" class="db-skill-check">
                <span class="inline-flex items-center gap-1"><x-griglia::icon name="ruler" /> {{ __('griglia::t.plan.as_plan') }}</span>
            </label>
            <div x-show="plan" x-cloak class="mt-1.5 space-y-1 px-1">
                <div class="flex items-start gap-1">
                    <textarea wire:model="planPrompt" rows="4" placeholder="{{ __('griglia::t.plan.prompt_placeholder') }}" class="db-plan-prompt w-full min-w-0 flex-1 rounded border-2 border-black px-2 py-1 text-sm focus:bg-emerald-50 focus:outline-none"></textarea>
                    <x-griglia::mic class="shrink-0 rounded border-2 border-black bg-white px-1.5 py-1 text-sm shadow-[1px_1px_0_#000]" within="form" target=".db-plan-prompt" />
                </div>
                <p class="text-[11px] opacity-60">{{ \Alle80\Griglia\Support\Plan::available() ? __('griglia::t.plan.hint') : __('griglia::t.plan.hint_no_ai') }}</p>
                <p class="text-[11px] font-bold" wire:loading wire:target="create">⏳ {{ __('griglia::t.plan.building') }}</p>
            </div>
        </form>

        {{-- Utente + navigazione (testo, niente icone) e logout --}}
        @php($logout = \Alle80\Griglia\Mode::isLocal() ? null : config('griglia.logout_route'))
        <form method="POST" action="{{ $logout && \Illuminate\Support\Facades\Route::has($logout) ? route($logout) : '#' }}" class="mt-1 border-t-2 border-black/20 px-1 pt-2 pb-1">
            @csrf
            <p class="mb-1.5 truncate px-1 text-xs opacity-60">{{ \Alle80\Griglia\Mode::isLocal() ? __('griglia::t.local_mode') : (auth()->user()?->name ?? '') }}</p>
            <nav class="grid grid-cols-2 gap-1" aria-label="{{ __('griglia::t.settings') }}">
                <a href="{{ route('griglia.stats') }}" class="rounded border-2 border-black bg-white px-2 py-1.5 text-center text-xs font-bold shadow-[1px_1px_0_#000] hover:bg-emerald-100 active:translate-y-px">{{ __('griglia::t.stats_page.menu') }}</a>
                <a href="{{ route('griglia.agents') }}" class="rounded border-2 border-black bg-white px-2 py-1.5 text-center text-xs font-bold shadow-[1px_1px_0_#000] hover:bg-emerald-100 active:translate-y-px">{{ __('griglia::t.agents.menu') }}</a>
                @if (\Alle80\Griglia\Admin::check())
                <a href="{{ route('griglia.context') }}" class="rounded border-2 border-black bg-white px-2 py-1.5 text-center text-xs font-bold shadow-[1px_1px_0_#000] hover:bg-emerald-100 active:translate-y-px">{{ __('griglia::t.ctx.menu') }}</a>
                <a href="{{ route('griglia.settings') }}" class="rounded border-2 border-black bg-white px-2 py-1.5 text-center text-xs font-bold shadow-[1px_1px_0_#000] hover:bg-emerald-100 active:translate-y-px">{{ __('griglia::t.settings') }}</a>
                @endif
                @if ($logout && \Illuminate\Support\Facades\Route::has($logout))
                    <button type="submit" class="cursor-pointer rounded border-2 border-black bg-white px-2 py-1.5 text-center text-xs font-bold text-red-700 shadow-[1px_1px_0_#000] hover:bg-red-50 active:translate-y-px">{{ __('griglia::t.logout') }}</button>
                @endif
            </nav>
        </form>
    </div>
</details>
@unless (\Alle80\Griglia\Mode::isLocal())
    <livewire:griglia::notification-bell />
@endunless
</div>
