@props([
    'target' => 'textarea, input',   // CSS selector of the field to dictate into, looked up inside $within
    'within' => 'form',              // closest ancestor selector from which $target is searched
    'class' => '',
])
{{--
    Speech-to-text button (Web Speech API, window.devboardMic): appends what you say to the nearest field.
    Hidden when the browser has no speech recognition (Chrome/Edge/Android/iOS Safari have it).
--}}
<button
    type="button"
    x-data="devboardMic(() => ($el.closest(@js($within)) || document).querySelector(@js($target)))"
    x-show="supported"
    x-cloak
    x-on:click="toggle()"
    x-bind:class="{ 'db-mic-on': on, 'db-mic-busy': busy, 'db-mic-error': error }"
    x-bind:title="error ? error : (busy ? @js(__('devboard::t.mic_busy')) : (on ? @js(__('devboard::t.mic_stop')) : @js(__('devboard::t.mic_start'))))"
    x-bind:aria-pressed="on ? 'true' : 'false'"
    x-bind:aria-busy="busy ? 'true' : 'false'"
    x-bind:disabled="busy"
    aria-label="{{ __('devboard::t.mic_start') }}"
    {{ $attributes->merge(['class' => 'db-mic cursor-pointer '.$class]) }}
><x-devboard::icon name="mic" /></button>
