{{-- Campanella delle notifiche della board (accanto al selettore liste; stesso look) --}}
<details
    class="relative"
    style="font-family: system-ui, sans-serif"
    x-data="{ open: false }"
    x-bind:open="open"
    x-on:toggle="open = $el.open"
    x-on:click.outside="open = false"
>
    <summary
        class="relative flex cursor-pointer list-none items-center rounded-lg border-2 border-black bg-white px-2 py-1 text-xs font-bold text-black shadow-[2px_2px_0_#000] select-none hover:bg-emerald-100 active:translate-y-px sm:px-2.5 sm:py-1.5 sm:text-sm [&::-webkit-details-marker]:hidden"
        title="{{ __('devboard::t.notif.bell') }}"
        aria-label="{{ __('devboard::t.notif.bell') }}{{ $unread ? ' ('.$unread.')' : '' }}"
    >
        <span aria-hidden="true">🔔</span>
        @if ($unread)
            <span class="db-bell-badge absolute -top-2 -right-2 min-w-5 rounded-full border-2 border-black bg-red-500 px-1 text-center text-[10px] leading-4 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </summary>
    <div class="absolute left-0 mt-1.5 max-h-[70vh] w-72 overflow-y-auto rounded-lg border-2 border-black bg-white p-1 text-black shadow-[3px_3px_0_#000] sm:w-80">
        <div class="flex items-center justify-between gap-2 px-2 py-1">
            <span class="text-xs font-bold uppercase opacity-60">{{ __('devboard::t.notif.title') }}</span>
            @if ($unread)
                <button type="button" wire:click="markAllRead" class="cursor-pointer text-xs font-bold hover:underline">{{ __('devboard::t.notif.mark_all') }}</button>
            @endif
        </div>
        @forelse ($items as $n)
            @php($d = (array) $n->data)
            <button
                type="button"
                wire:key="notif-{{ $n->id }}"
                wire:click="openNotification('{{ $n->id }}')"
                class="flex w-full cursor-pointer items-start gap-2 rounded px-2 py-1.5 text-left {{ $n->read_at ? 'opacity-60 hover:bg-emerald-50' : 'bg-emerald-100 hover:bg-emerald-200' }}"
            >
                <span class="shrink-0 text-base" aria-hidden="true">{{ $d['icon'] ?? '🔔' }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold">{{ $d['title'] ?? '' }}</span>
                    @if (! empty($d['body']))<span class="block text-xs opacity-80">{{ \Illuminate\Support\Str::limit($d['body'], 120) }}</span>@endif
                    <span class="block text-[10px] opacity-50">{{ $n->created_at->diffForHumans() }}</span>
                </span>
                @unless ($n->read_at)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-red-500" aria-hidden="true"></span>@endunless
            </button>
        @empty
            <p class="px-2 py-3 text-center text-xs opacity-60">{{ __('devboard::t.notif.none') }}</p>
        @endforelse
    </div>
</details>
