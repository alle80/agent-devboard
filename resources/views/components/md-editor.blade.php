@props([
    'model',                 // wire:model target
    'rows' => 4,
    'placeholder' => '',
    'inputClass' => '',
    'live' => false,         // true = salvataggio live: la bozza va al componente a ogni pausa
    'debounce' => '800ms',
])
@php($wireModel = $live ? 'wire:model.live.debounce.'.$debounce : 'wire:model')
{{--
    Markdown editor: a textarea with a small toolbar that inserts Markdown syntax. The value stays
    bound to Livewire via wire:model; buttons edit the textarea and dispatch 'input' to sync.
--}}
<div class="db-md" x-data="{
    ta() { return this.$refs.ta; },
    grow() { const t = this.ta(); t.style.height = 'auto'; t.style.height = Math.max(t.scrollHeight, 40) + 'px'; },
    sync() { this.ta().dispatchEvent(new Event('input', { bubbles: true })); this.grow(); this.ta().focus(); },
    wrap(before, after, ph) {
        const t = this.ta(); const s = t.selectionStart, e = t.selectionEnd;
        const sel = t.value.slice(s, e) || ph;
        t.value = t.value.slice(0, s) + before + sel + after + t.value.slice(e);
        t.selectionStart = s + before.length; t.selectionEnd = s + before.length + sel.length;
        this.sync();
    },
    prefix(p) {
        const t = this.ta(); const s = t.selectionStart, e = t.selectionEnd;
        const ls = t.value.lastIndexOf('\n', s - 1) + 1;
        const lines = t.value.slice(ls, e).split('\n').map(l => p + l).join('\n');
        t.value = t.value.slice(0, ls) + lines + t.value.slice(e);
        this.sync();
    },
    insert(text) {
        const t = this.ta(); const s = t.selectionStart;
        t.value = t.value.slice(0, s) + text + t.value.slice(t.selectionEnd);
        t.selectionStart = t.selectionEnd = s + text.length;
        this.sync();
    },
}">
    <div class="db-md-bar">
        <button type="button" class="db-md-btn" @click="wrap('**','**','{{ __('griglia::t.md.bold') }}')" title="{{ __('griglia::t.md.bold') }}"><span style="font-weight:800">B</span></button>
        <button type="button" class="db-md-btn" @click="wrap('*','*','{{ __('griglia::t.md.italic') }}')" title="{{ __('griglia::t.md.italic') }}"><span style="font-style:italic">I</span></button>
        <button type="button" class="db-md-btn" @click="wrap('`','`','{{ __('griglia::t.md.code') }}')" title="{{ __('griglia::t.md.code') }}"><span style="font-family:monospace">&lt;&gt;</span></button>
        <button type="button" class="db-md-btn" @click="insert('\n```\n{{ __('griglia::t.md.code') }}\n```\n')" title="{{ __('griglia::t.md.codeblock') }}"><span style="font-family:monospace">{ }</span></button>
        <span class="db-md-sep"></span>
        <button type="button" class="db-md-btn" @click="prefix('- ')" title="{{ __('griglia::t.md.list') }}">&bull;</button>
        <button type="button" class="db-md-btn" @click="prefix('> ')" title="{{ __('griglia::t.md.quote') }}">&ldquo;</button>
        <button type="button" class="db-md-btn" @click="wrap('[','](https://)','{{ __('griglia::t.md.linktext') }}')" title="{{ __('griglia::t.md.link') }}">&#128279;</button>
        <span class="db-md-sep"></span>
        <button type="button" class="db-md-btn" @click="insert('\n| a | b |\n| --- | --- |\n| 1 | 2 |\n')" title="{{ __('griglia::t.md.table') }}">&#8862;</button>
        <button type="button" class="db-md-btn" @click="insert('\n\n---\n\n')" title="{{ __('griglia::t.md.separator') }}">&mdash;</button>
        <span class="db-md-sep"></span>
        <x-griglia::mic class="db-md-btn" within=".db-md" target="textarea" />
    </div>
    {{-- Con live: l'uscita dal campo manda subito quello che c'è, senza aspettare il debounce --}}
    <textarea x-ref="ta" {{ $wireModel }}="{{ $model }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
              @if ($live) @blur="$wire.set('{{ $model }}', $event.target.value)" @endif
              x-init="$nextTick(() => grow())" @input="grow()" style="overflow:hidden; resize:none;"
              {{ $attributes->merge(['class' => 'db-md-input '.$inputClass]) }}></textarea>
</div>
