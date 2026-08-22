{{-- Selettore della lista corrente, identico su tutte le pagine. Prende look e font dal tema
     corrente (.tl-btn / .tl-menu): niente stile proprio hard-coded. --}}
@php($current = $lists->firstWhere('id', $currentId))
<div class="tl-chrome fixed top-3 left-3 z-[60]">
<details
    class="relative"
    x-data="{ open: false }"
    x-bind:open="open"
    x-on:toggle="open = $el.open"
    x-on:click.outside="open = false"
>
    <summary class="tl-btn max-w-[48vw] list-none sm:max-w-xs [&::-webkit-details-marker]:hidden">
        <x-griglia::icon name="list" />
        <span class="truncate">{{ $current?->name ?? __('griglia::t.lists') }}</span>
        @if ($current && $current->todos_count > 0)
            <span class="text-[11px] tabular-nums opacity-60">{{ $current->done_count }}/{{ $current->todos_count }}</span>
        @endif
        <x-griglia::icon name="chevron" size=".9em" class="tl-caret" />
    </summary>
    <div class="tl-menu absolute left-0 mt-1.5 max-h-[70vh] w-72 overflow-y-auto p-1.5">

        <p class="tl-menu-label px-2 pt-1 pb-1.5">{{ $showArchived ? __('griglia::t.lists_archive') : __('griglia::t.lists') }}</p>

        @if ($showArchived)
            <p class="mb-1 px-2 text-[11px] opacity-60">{{ __('griglia::t.lists_archive_help') }}</p>
        @endif

        @forelse ($lists as $list)
            <div wire:key="list-{{ $list->id }}" class="tl-menu-item flex items-center gap-1 {{ $list->id === $currentId ? 'is-current' : '' }}">
                @if ($editingId === $list->id)
                    <form wire:submit="saveRename" class="flex min-w-0 flex-1 items-center gap-1 p-1">
                        <input
                            type="text"
                            wire:model="nameDraft"
                            wire:keydown.escape="cancelRename"
                            x-init="$el.focus(); $el.select()"
                            class="tl-input w-full min-w-0 flex-1 px-2 py-1 text-sm font-bold focus:outline-none"
                        >
                        <button type="submit" title="{{ __('griglia::t.save') }}" aria-label="{{ __('griglia::t.save') }}" class="tl-btn tl-btn-icon shrink-0"><x-griglia::icon name="check" :stroke="2.5" /></button>
                        <button type="button" wire:click="cancelRename" title="{{ __('griglia::t.cancel') }}" aria-label="{{ __('griglia::t.cancel') }}" class="tl-btn tl-btn-icon tl-btn-ghost shrink-0"><x-griglia::icon name="close" /></button>
                    </form>
                @else
                    <button
                        wire:click="{{ $showArchived ? 'restoreList('.$list->id.')' : 'switchTo('.$list->id.')' }}"
                        title="{{ $showArchived ? __('griglia::t.restore_list') : '' }}"
                        class="min-w-0 flex-1 cursor-pointer px-2 py-1.5 text-left text-sm font-bold"
                    >
                        <span class="flex items-center gap-1.5">
                            <span class="min-w-0 flex-1 truncate">{{ $list->name }}</span>
                            @if ($list->id === $currentId)<x-griglia::icon name="check" size=".9em" />@endif
                            <span class="shrink-0 text-[11px] font-normal tabular-nums opacity-55">{{ $list->done_count }}/{{ $list->todos_count }}</span>
                        </span>
                        @if ($list->todos_count > 0)
                            <span class="tl-meter mt-1 block" aria-hidden="true"><span style="width: {{ (int) round($list->done_count / max($list->todos_count, 1) * 100) }}%"></span></span>
                        @endif
                    </button>
                    @if ($showArchived)
                        <button
                            wire:click="restoreList({{ $list->id }})"
                            title="{{ __('griglia::t.restore_list') }}"
                            aria-label="{{ __('griglia::t.restore_list') }}"
                            class="tl-btn tl-btn-icon shrink-0"
                        ><x-griglia::icon name="restore" /></button>
                    @endif
                    @if (! $showArchived && ($list->chained_count > 0 || $list->plan_prompt) && $list->running_count > 0)
                        {{-- Plan running: the agent follows the chain --}}
                        <span class="db-badge db-badge-working shrink-0 px-1" title="{{ __('griglia::t.plan.running_short') }}" aria-label="{{ __('griglia::t.plan.running_short') }}"><x-griglia::icon name="working" :stroke="2" /></span>
                    @endif
                    @if (! $showArchived && ($list->chained_count > 0 || $list->plan_prompt) && $list->running_count === 0 && $list->done_count < $list->todos_count)
                        {{-- Plan list not running: start it from here --}}
                        <button
                            wire:click="startPlan({{ $list->id }})"
                            title="{{ $list->done_count > 0 ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}"
                            aria-label="{{ $list->done_count > 0 ? __('griglia::t.plan.resume') : __('griglia::t.plan.start') }}"
                            class="tl-btn tl-btn-icon shrink-0"
                        ><x-griglia::icon name="play" /></button>
                    @endif
                    @unless ($showArchived)
                        <button
                            wire:click="startRename({{ $list->id }})"
                            title="{{ __('griglia::t.rename_list') }}"
                            aria-label="{{ __('griglia::t.rename_list') }}"
                            class="tl-btn tl-btn-icon tl-btn-ghost shrink-0"
                        ><x-griglia::icon name="edit" /></button>
                        @if ($lists->count() > 1)
                            <button
                                wire:click="archiveList({{ $list->id }})"
                                title="{{ __('griglia::t.archive_list') }}"
                                aria-label="{{ __('griglia::t.archive_list') }}"
                                class="tl-btn tl-btn-icon tl-btn-ghost shrink-0"
                            ><x-griglia::icon name="archive" /></button>
                        @endif
                    @endunless
                    @if ($showArchived || $lists->count() > 1)
                        <button
                            wire:click="deleteList({{ $list->id }})"
                            wire:confirm="{{ __('griglia::t.delete_list_confirm', ['name' => $list->name, 'count' => $list->todos_count]) }}"
                            title="{{ __('griglia::t.delete_list') }}"
                            aria-label="{{ __('griglia::t.delete_list') }}"
                            class="tl-btn tl-btn-icon tl-btn-ghost tl-btn-danger shrink-0"
                        ><x-griglia::icon name="trash" /></button>
                    @endif
                @endif
            </div>
        @empty
            <p class="px-2 py-3 text-center text-xs opacity-60">{{ __('griglia::t.lists_archive_empty') }}</p>
        @endforelse

        {{-- Archivio delle liste: si entra e si esce da qui --}}
        <div class="tl-menu-sep mt-1.5 p-1 pt-2">
            <button type="button" wire:click="toggleArchived" class="tl-btn tl-btn-sm w-full justify-center" aria-pressed="{{ $showArchived ? 'true' : 'false' }}">
                <x-griglia::icon :name="$showArchived ? 'restore' : 'archive'" />
                {{ $showArchived ? __('griglia::t.back_to_active') : __('griglia::t.lists_archive') }} ({{ $archivedCount }})
            </button>
        </div>

        {{-- Nuova lista --}}
        <form wire:submit="create" class="tl-menu-sep mt-1.5 p-1 pt-2" @if ($showArchived) hidden @endif>
            <div class="flex items-center gap-1">
                <input
                    type="text"
                    wire:model="newName"
                    placeholder="{{ __('griglia::t.new_list') }}"
                    class="tl-input w-full min-w-0 flex-1 px-2 py-1 text-sm focus:outline-none"
                >
                <button type="submit" class="tl-btn tl-btn-icon shrink-0" wire:loading.attr="disabled" wire:target="create" aria-label="{{ __('griglia::t.new_list') }}">
                    <span wire:loading.remove wire:target="create"><x-griglia::icon name="plus" :stroke="2.5" /></span><span wire:loading wire:target="create">…</span>
                </button>
            </div>
            {{-- Un piano si scrive in una pagina sua: qui non c'è spazio (task 342) --}}
            <a href="{{ route('griglia.plans.index') }}" class="tl-btn tl-btn-sm mt-1.5 w-full justify-start">
                <x-griglia::icon name="ruler" /> {{ __('griglia::t.plan.index_menu') }}
            </a>

        </form>

        {{-- Utente, pagine e uscita --}}
        @php($logout = \Alle80\Griglia\Mode::isLocal() ? null : config('griglia.logout_route'))
        <form method="POST" action="{{ $logout && \Illuminate\Support\Facades\Route::has($logout) ? route($logout) : '#' }}" class="tl-menu-sep mt-1.5 px-1 pt-2 pb-1">
            @csrf
            <p class="tl-menu-label mb-1.5 truncate px-1">{{ \Alle80\Griglia\Mode::isLocal() ? __('griglia::t.local_mode') : (auth()->user()?->name ?? '') }}</p>
            <nav class="grid grid-cols-2 gap-1" aria-label="{{ __('griglia::t.settings') }}">
                <a href="{{ route('griglia.stats') }}" class="tl-btn tl-btn-sm justify-center">{{ __('griglia::t.stats_page.menu') }}</a>
                <a href="{{ route('griglia.agents') }}" class="tl-btn tl-btn-sm justify-center">{{ __('griglia::t.agents.menu') }}</a>
                @if (\Alle80\Griglia\Admin::check())
                <a href="{{ route('griglia.context') }}" class="tl-btn tl-btn-sm justify-center">{{ __('griglia::t.ctx.menu') }}</a>
                <a href="{{ route('griglia.settings') }}" class="tl-btn tl-btn-sm justify-center">{{ __('griglia::t.settings') }}</a>
                @endif
                @if ($logout && \Illuminate\Support\Facades\Route::has($logout))
                    <button type="submit" class="tl-btn tl-btn-sm tl-btn-danger col-span-2 justify-center">{{ __('griglia::t.logout') }}</button>
                @endif
            </nav>
        </form>
    </div>
</details>
</div>
