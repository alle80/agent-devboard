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
        📋 {{ $lists->firstWhere('id', $currentId)?->name ?? __('devboard::t.lists') }} ▾
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
                        <button type="submit" title="{{ __('devboard::t.save') }}" class="shrink-0 cursor-pointer rounded border-2 border-black bg-emerald-200 px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" aria-label="{{ __('devboard::t.save') }}">✔</button>
                        <button type="button" wire:click="cancelRename" title="{{ __('devboard::t.cancel') }}" class="shrink-0 cursor-pointer rounded border-2 border-black bg-white px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" aria-label="{{ __('devboard::t.cancel') }}">✕</button>
                    </form>
                @else
                    <button
                        wire:click="switchTo({{ $list->id }})"
                        class="min-w-0 flex-1 cursor-pointer px-2 py-1.5 text-left text-sm font-bold"
                    >
                        <span class="block truncate">{{ $list->name }}{{ $list->id === $currentId ? ' ✓' : '' }}</span>
                        <span class="block text-xs font-normal opacity-60">{{ $list->done_count }}/{{ $list->todos_count }} {{ __('devboard::t.done_short') }}</span>
                    </button>
                    <button
                        wire:click="startRename({{ $list->id }})"
                        title="{{ __('devboard::t.rename_list') }}"
                        class="shrink-0 cursor-pointer px-1.5 py-1.5 text-sm opacity-30 hover:opacity-100"
                    >✏️</button>
                    @if ($lists->count() > 1)
                        <button
                            wire:click="deleteList({{ $list->id }})"
                            wire:confirm="{{ __('devboard::t.delete_list_confirm', ['name' => $list->name, 'count' => $list->todos_count]) }}"
                            title="{{ __('devboard::t.delete_list') }}"
                            class="shrink-0 cursor-pointer px-2 py-1.5 text-sm opacity-30 hover:text-red-600 hover:opacity-100"
                        >✕</button>
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
                    placeholder="{{ __('devboard::t.new_list') }}"
                    class="w-full min-w-0 flex-1 rounded border-2 border-black px-2 py-1 text-sm focus:bg-emerald-50 focus:outline-none"
                >
                <button type="submit" class="shrink-0 cursor-pointer rounded border-2 border-black bg-emerald-200 px-2 py-1 text-sm font-bold shadow-[1px_1px_0_#000] active:translate-y-px" wire:loading.attr="disabled" wire:target="create">
                    <span wire:loading.remove wire:target="create">+</span><span wire:loading wire:target="create">…</span>
                </button>
            </div>
            {{-- Plan mode: build the list from a prompt (chained tasks) --}}
            <label class="mt-1.5 flex cursor-pointer items-center gap-1.5 px-1 text-xs select-none">
                <input type="checkbox" x-model="plan" class="db-skill-check">
                <span>📐 {{ __('devboard::t.plan.as_plan') }}</span>
            </label>
            <div x-show="plan" x-cloak class="mt-1.5 space-y-1 px-1">
                <div class="flex items-start gap-1">
                    <textarea wire:model="planPrompt" rows="4" placeholder="{{ __('devboard::t.plan.prompt_placeholder') }}" class="db-plan-prompt w-full min-w-0 flex-1 rounded border-2 border-black px-2 py-1 text-sm focus:bg-emerald-50 focus:outline-none"></textarea>
                    <x-devboard::mic class="shrink-0 rounded border-2 border-black bg-white px-1.5 py-1 text-sm shadow-[1px_1px_0_#000]" within="form" target=".db-plan-prompt" />
                </div>
                <p class="text-[11px] opacity-60">{{ \Alle80\Devboard\Support\Plan::available() ? __('devboard::t.plan.hint') : __('devboard::t.plan.hint_no_ai') }}</p>
                <p class="text-[11px] font-bold" wire:loading wire:target="create">⏳ {{ __('devboard::t.plan.building') }}</p>
            </div>
        </form>

        {{-- Utente e logout --}}
        @php($logout = \Alle80\Devboard\Mode::isLocal() ? null : config('devboard.logout_route'))
        <form method="POST" action="{{ $logout && \Illuminate\Support\Facades\Route::has($logout) ? route($logout) : '#' }}" class="mt-1 flex items-center justify-between gap-2 border-t-2 border-black/20 px-2 pt-2 pb-1">
            @csrf
            <span class="min-w-0 truncate text-xs opacity-60">👤 {{ \Alle80\Devboard\Mode::isLocal() ? __('devboard::t.local_mode') : (auth()->user()?->name ?? '') }}</span>
            <a href="{{ route('devboard.context') }}" class="shrink-0 text-xs font-bold hover:underline" title="{{ __('devboard::t.ctx.title') }}">📚 {{ __('devboard::t.ctx.menu') }}</a>
            <a href="{{ route('devboard.settings') }}" class="shrink-0 text-xs font-bold hover:underline" title="{{ __('devboard::t.settings') }}">⚙️ {{ __('devboard::t.settings') }}</a>
            @if ($logout && \Illuminate\Support\Facades\Route::has($logout))
                <button type="submit" class="shrink-0 cursor-pointer text-xs font-bold text-red-700 hover:underline">{{ __('devboard::t.logout') }}</button>
            @endif
        </form>
    </div>
</details>
@unless (\Alle80\Devboard\Mode::isLocal())
    <livewire:devboard::notification-bell />
@endunless
</div>
