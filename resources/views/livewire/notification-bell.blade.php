{{-- Campanella delle notifiche della board (accanto al selettore liste; stesso look, dal tema) --}}
<details
    class="relative"
    x-data="{ open: false }"
    x-bind:open="open"
    x-on:toggle="open = $el.open"
    x-on:click.outside="open = false"
>
    <summary
        class="tl-btn tl-btn-icon relative list-none [&::-webkit-details-marker]:hidden"
        title="{{ __('griglia::t.notif.bell') }}"
        aria-label="{{ __('griglia::t.notif.bell') }}{{ $unread ? ' ('.$unread.')' : '' }}"
    >
        <x-griglia::icon name="bell" size="1.2em" />
        @if ($unread)
            <span class="db-bell-badge absolute -top-2 -right-2 min-w-5 rounded-full border border-current bg-red-500 px-1 text-center text-[10px] leading-4 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </summary>
    <div class="db-bell-list tl-menu fixed right-3 left-3 mt-1.5 max-h-[70vh] overflow-y-auto p-1.5 sm:absolute sm:right-auto sm:left-0 sm:w-80">
        <div class="flex items-center justify-between gap-2 px-2 py-1">
            <span class="tl-menu-label">{{ __('griglia::t.notif.title') }}</span>
            @if ($unread)
                <button type="button" wire:click="markAllRead" class="cursor-pointer text-xs font-bold hover:underline">{{ __('griglia::t.notif.mark_all') }}</button>
            @endif
        </div>
        @forelse ($items as $n)
            @php($d = (array) $n->data)
            <button
                type="button"
                wire:key="notif-{{ $n->id }}"
                wire:click="openNotification('{{ $n->id }}')"
                class="tl-menu-item flex w-full cursor-pointer items-start gap-2 px-2 py-1.5 text-left {{ $n->read_at ? 'opacity-55' : 'is-current' }}"
            >
                @php($kindIcon = ['todo_completed' => 'done', 'question_asked' => 'question', 'test' => 'bell'][$d['kind'] ?? ''] ?? 'bell')
                <span class="db-badge db-badge-{{ $kindIcon === 'done' ? 'done' : ($kindIcon === 'question' ? 'question' : 'open') }} shrink-0" aria-hidden="true"><x-griglia::icon :name="$kindIcon" size="1.2em" :stroke="2" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold">{{ $d['title'] ?? '' }}</span>
                    @if (! empty($d['body']))<span class="block text-xs opacity-80">{{ \Illuminate\Support\Str::limit($d['body'], 120) }}</span>@endif
                    <span class="block text-[10px] opacity-50">{{ $n->created_at->diffForHumans() }}</span>
                </span>
                @unless ($n->read_at)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-red-500" aria-hidden="true"></span>@endunless
            </button>
        @empty
            <p class="px-2 py-3 text-center text-xs opacity-60">{{ __('griglia::t.notif.none') }}</p>
        @endforelse
    </div>
</details>
