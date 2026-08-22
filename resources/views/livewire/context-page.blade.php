<div class="mx-auto w-full max-w-3xl px-4 pt-24 pb-16 sm:pt-24 lg:max-w-5xl xl:px-8" style="{{ $skin['vars'] }}">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} db-ctx-h1 inline-flex items-center gap-2"><x-griglia::icon name="book" size="1em" /> {{ __('griglia::t.ctx.title', ['agent' => \Alle80\Griglia\Agent::name()]) }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>
    <p class="{{ $skin['sub'] }} mb-2">{{ __('griglia::t.ctx.intro') }}</p>
    <p class="{{ $skin['help'] }} mb-5 tabular-nums">
        <x-griglia::icon name="coins" /> {{ __('griglia::t.ctx.tokens', ['on' => number_format($tokensOn), 'total' => number_format($tokensTotal)]) }}
        @if ($tokensTotal) · {{ (int) round($tokensOn / max(1, $tokensTotal) * 100) }}% @endif
    </p>

    {{-- Generate the instruction files from the board, or keep the original files --}}
    <div class="{{ $skin['card'] }} mb-4 flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="{{ $skin['label'] }} text-sm">{{ __('griglia::t.ctx.sync_label') }}</p>
            <p class="{{ $skin['help'] }} text-xs">{{ __('griglia::t.ctx.sync_help') }}</p>
        </div>
        <button type="button" role="switch" aria-checked="{{ $syncOn ? 'true' : 'false' }}" aria-label="{{ __('griglia::t.ctx.sync_label') }}" wire:click="toggleSync" class="setting-switch mt-1 shrink-0 {{ $syncOn ? 'is-on' : '' }}"><span class="setting-knob"></span></button>
    </div>

    {{-- Bulk bar (sticky while something is selected) --}}
    <div class="{{ $skin['card'] }} db-ctx-bulk sticky top-14 z-20 mb-4 flex flex-wrap items-center gap-2 py-2 {{ $selected ? '' : 'hidden' }}" aria-live="polite">
        <span class="{{ $skin['label'] }} text-sm">{{ __('griglia::t.ctx.selected', ['count' => count($selected)]) }}</span>
        <button type="button" wire:click="setSelected(true)" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm"><x-griglia::icon name="check-all" /> {{ __('griglia::t.ctx.enable_selected') }}</button>
        <button type="button" wire:click="setSelected(false)" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm"><x-griglia::icon name="ban" /> {{ __('griglia::t.ctx.disable_selected') }}</button>
        <button type="button" wire:click="clearSelection" class="{{ $skin['help'] }} cursor-pointer text-sm hover:underline">{{ __('griglia::t.ctx.clear_selection') }}</button>
    </div>

    <div
        x-data
        x-init="Sortable.create($el, { handle: '.ctx-group-handle', draggable: '[data-group-id]', animation: 150, ghostClass: 'opacity-30', onEnd: () => $wire.reorderGroups(Array.from($el.querySelectorAll('[data-group-id]')).map(e => e.dataset.groupId)) })"
        class="space-y-4"
    >
        @foreach ($groups as $g)
            @php($gOn = $g->blocks->where('enabled', true)->count())
            <details
                class="{{ $skin['card'] }} db-ctx-group {{ $g->enabled ? '' : 'db-ctx-off' }}"
                data-group-id="{{ $g->id }}"
                wire:key="ctx-group-{{ $g->id }}"
                x-data="{ o: false }" x-bind:open="o" x-on:toggle="o = $el.open"
            >
                {{-- Card header: commands on the top row (handle, switch, actions, chevron), title + stats below on their own line --}}
                <summary class="flex cursor-pointer flex-col gap-2 select-none [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center gap-2">
                        <span class="ctx-group-handle cursor-grab leading-none opacity-30 hover:opacity-100" title="{{ __('griglia::t.drag_to_reorder') }}" x-on:click.prevent.stop><x-griglia::icon name="grip" size="1.3em" /></span>
                        <button type="button" role="switch" aria-checked="{{ $g->enabled ? 'true' : 'false' }}" aria-label="{{ $g->title }}"
                            wire:click="toggleGroup({{ $g->id }})" x-on:click.stop
                            class="setting-switch shrink-0 {{ $g->enabled ? 'is-on' : '' }}"><span class="setting-knob"></span></button>
                        <span class="flex-1"></span>
                        <span class="flex shrink-0 items-center gap-2 text-sm" x-on:click.stop>
                            <button type="button" wire:click="selectGroup({{ $g->id }}, true)" class="cursor-pointer opacity-60 hover:opacity-100" title="{{ __('griglia::t.ctx.select_all') }}" aria-label="{{ __('griglia::t.ctx.select_all') }}"><x-griglia::icon name="check-all" size="1.2em" /></button>
                            <button type="button" wire:click="startRenameGroup({{ $g->id }})" class="cursor-pointer opacity-60 hover:opacity-100" title="{{ __('griglia::t.ctx.rename') }}" aria-label="{{ __('griglia::t.ctx.rename') }}"><x-griglia::icon name="edit" size="1.2em" /></button>
                            <button type="button" wire:click="deleteGroup({{ $g->id }})" wire:confirm="{{ __('griglia::t.ctx.delete_group_confirm', ['title' => $g->title]) }}" class="cursor-pointer opacity-60 hover:opacity-100" title="{{ __('griglia::t.ctx.delete') }}" aria-label="{{ __('griglia::t.ctx.delete') }}"><x-griglia::icon name="trash" size="1.2em" /></button>
                        </span>
                        <span class="shrink-0 opacity-60 transition-transform" aria-hidden="true" x-bind:class="o ? 'rotate-180' : ''"><x-griglia::icon name="chevron" /></span>
                    </span>
                    <span class="block min-w-0">
                        @if ($renamingGroupId === $g->id)
                            <form wire:submit="saveGroup" x-on:click.stop class="flex items-center gap-2">
                                <input type="text" wire:model="groupDraft" class="{{ $skin['input'] }} w-full" x-init="$el.focus()" wire:keydown.escape="$set('renamingGroupId', null)">
                                <button type="submit" class="{{ $skin['back'] }} text-sm" aria-label="{{ __('griglia::t.save') }}"><x-griglia::icon name="check" /></button>
                            </form>
                        @else
                            <span class="{{ $skin['h2'] }} db-ctx-title block break-words">{{ $g->title }}</span>
                            <span class="{{ $skin['help'] }} text-xs">{{ __('griglia::t.ctx.group_stats', ['on' => $gOn, 'total' => $g->blocks->count(), 'tokens' => number_format($g->blocks->where('enabled', true)->sum->tokens())]) }}</span>
                        @endif
                    </span>
                </summary>

                <ul
                    class="mt-3 space-y-2"
                    x-data
                    x-init="Sortable.create($el, { handle: '.ctx-block-handle', draggable: '[data-block-id]', animation: 150, ghostClass: 'opacity-30', onEnd: () => $wire.reorderBlocks({{ $g->id }}, Array.from($el.querySelectorAll('[data-block-id]')).map(e => e.dataset.blockId)) })"
                >
                    @forelse ($g->blocks as $b)
                        @php($sel = in_array($b->id, $selected, true))
                        <li data-block-id="{{ $b->id }}" wire:key="ctx-block-{{ $b->id }}" class="db-ctx-block flex flex-wrap items-start gap-2 rounded border border-current/15 px-2 py-2 {{ $b->enabled ? '' : 'db-ctx-off' }} {{ $sel ? 'db-ctx-selected' : '' }}">
                            <span class="ctx-block-handle mt-0.5 cursor-grab opacity-30 hover:opacity-100" title="{{ __('griglia::t.drag_to_reorder') }}"><x-griglia::icon name="grip" size="1.2em" /></span>
                            <input type="checkbox" class="db-skill-check mt-1 shrink-0" @checked($sel) wire:click="toggleSelect({{ $b->id }})" aria-label="{{ __('griglia::t.ctx.select') }}: {{ $b->title }}">
                            <span class="{{ $skin['help'] }} ml-1 text-xs tabular-nums">≈{{ $b->tokens() }} tok</span>
                            <span class="flex-1"></span>
                            <div class="order-last min-w-0 basis-full">
                                @if ($editingId === $b->id)
                                    <form wire:submit="saveEdit" class="space-y-2">
                                        <input type="text" wire:model="titleDraft" placeholder="{{ __('griglia::t.ctx.block_title') }}" class="{{ $skin['input'] }} w-full text-sm">
                                        <x-griglia::md-editor model="bodyDraft" :rows="8" :inputClass="$skin['input'].' w-full font-mono text-xs db-ctx-editor'" wire:keydown.escape="cancelEdit" />
                                        <p class="{{ $skin['help'] }} text-xs">{{ __('griglia::t.md_hint') }}</p>
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" wire:click="cancelEdit" class="{{ $skin['help'] }} cursor-pointer text-sm hover:underline">{{ __('griglia::t.cancel') }}</button>
                                            <button type="submit" class="{{ $skin['back'] }} text-sm">{{ __('griglia::t.save') }}</button>
                                        </div>
                                    </form>
                                @elseif ($b->isManaged())
                                    {{-- Written by the board from a setting (task 499): shown here, changed in /settings --}}
                                    <div class="block w-full text-left" title="{{ __('griglia::t.ctx.managed_hint') }}">
                                        <span class="{{ $skin['label'] }} text-sm">{{ $b->title ?: '—' }}</span>
                                        <a href="{{ route('griglia.settings') }}" class="{{ $skin['help'] }} ml-2 inline-flex items-center gap-1 text-xs hover:underline"><x-griglia::icon name="settings" /> {{ __('griglia::t.ctx.managed') }}</a>
                                        <span class="{{ $skin['help'] }} db-ctx-preview mt-0.5 block text-xs whitespace-pre-wrap break-words">{{ \Illuminate\Support\Str::limit($b->body, 260) }}</span>
                                    </div>
                                @else
                                    <button type="button" wire:click="startEdit({{ $b->id }})" class="block w-full cursor-text text-left" title="{{ __('griglia::t.ctx.edit') }}">
                                        <span class="{{ $skin['label'] }} text-sm">{{ $b->title ?: '—' }}</span>
                                        <span class="{{ $skin['help'] }} db-ctx-preview mt-0.5 block text-xs whitespace-pre-wrap break-words">{{ \Illuminate\Support\Str::limit($b->body, 260) }}</span>
                                    </button>
                                @endif
                            </div>
                            <button type="button" role="switch" aria-checked="{{ $b->enabled ? 'true' : 'false' }}" aria-label="{{ $b->title }}"
                                wire:click="toggleBlock({{ $b->id }})" class="setting-switch mt-0.5 shrink-0 {{ $b->enabled ? 'is-on' : '' }}"><span class="setting-knob"></span></button>
                            <button type="button" wire:click="deleteBlock({{ $b->id }})" wire:confirm="{{ __('griglia::t.ctx.delete_block_confirm') }}" class="mt-0.5 shrink-0 cursor-pointer opacity-40 hover:opacity-100" title="{{ __('griglia::t.ctx.delete') }}" aria-label="{{ __('griglia::t.ctx.delete') }}"><x-griglia::icon name="trash" size="1.2em" /></button>
                        </li>
                    @empty
                        <li class="{{ $skin['help'] }} py-2 text-sm italic">{{ __('griglia::t.ctx.no_blocks') }}</li>
                    @endforelse
                </ul>
                <button type="button" wire:click="addBlock({{ $g->id }})" class="{{ $skin['back'] }} mt-3 inline-flex items-center gap-1 text-sm"><x-griglia::icon name="plus" /> {{ __('griglia::t.ctx.add_block') }}</button>
            </details>
        @endforeach
    </div>

    <form wire:submit="addGroup" class="{{ $skin['card'] }} mt-4 flex items-center gap-2">
        <input type="text" wire:model="newGroup" placeholder="{{ __('griglia::t.ctx.new_group') }}" class="{{ $skin['input'] }} w-full">
        <button type="submit" class="{{ $skin['back'] }} inline-flex shrink-0 items-center gap-1"><x-griglia::icon name="plus" /> {{ __('griglia::t.ctx.add_group') }}</button>
    </form>

    @if ($groups->isEmpty())
        <p class="{{ $skin['help'] }} mt-6 text-center">{{ __('griglia::t.ctx.empty') }}</p>
    @endif
    <p class="{{ $skin['help'] }} mt-6 text-center text-xs">{{ __('griglia::t.ctx.how') }}</p>
</div>
