{{--
    Notifiche toast globali. Da Livewire: $this->dispatch('toast', message: '…', type: 'success'|'error'|'info').
    Da JS/Alpine: window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } })).
--}}
<div
    x-data="{
        toasts: [],
        add(detail) {
            const t = { id: Date.now() + Math.random(), message: detail.message ?? String(detail), type: detail.type ?? 'success' }
            this.toasts.push(t)
            setTimeout(() => this.remove(t.id), detail.duration ?? 3200)
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) },
    }"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[90] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6"
    style="font-family: system-ui, sans-serif"
    aria-live="polite"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex max-w-sm items-center gap-2 rounded-lg border-2 border-black px-3 py-2 text-sm font-bold text-black shadow-[3px_3px_0_#000]"
            :class="{ 'bg-emerald-200': t.type === 'success', 'bg-red-200': t.type === 'error', 'bg-amber-100': t.type === 'info' }"
            x-on:click="remove(t.id)"
            role="status"
        >
            <span x-text="t.type === 'error' ? '⚠️' : (t.type === 'info' ? 'ℹ️' : '✅')"></span>
            <span x-text="t.message"></span>
        </div>
    </template>
</div>
