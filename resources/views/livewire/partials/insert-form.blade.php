<form wire:submit="saveInsert" x-data="{ len: 0 }" class="tl-card my-2 flex items-center gap-2 px-3 py-2">
    <span class="tl-display text-2xl">+</span>
    <input
        type="text"
        wire:model="newTitle"
        maxlength="{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}"
        x-on:input="len = $event.target.value.length"
        wire:keydown.escape="cancelInsert"
        x-init="$el.focus()"
        placeholder="{{ $t['placeholder'] }}"
        class="tl-input w-full min-w-0 flex-1 px-3 py-1.5 focus:outline-none"
    >
    <x-devboard::mic class="tl-check tl-display shrink-0 px-2 py-1.5" within="form" target="input" />
    <span class="shrink-0 text-xs tabular-nums opacity-60" x-text="len + '/{{ \Alle80\Devboard\Livewire\TodoList::titleMax() }}'" aria-hidden="true"></span>
    <button type="submit" class="tl-check tl-display shrink-0 cursor-pointer px-3 py-1.5 transition active:translate-y-px">{{ __('devboard::t.ok') }}</button>
    <button type="button" wire:click="cancelInsert" class="tl-check tl-display shrink-0 cursor-pointer px-3 py-1.5 transition active:translate-y-px"><x-devboard::icon name="close" /></button>
</form>
