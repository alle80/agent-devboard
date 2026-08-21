@props([
    'target' => 'textarea, input',   // CSS selector of the field to dictate into, looked up inside $within
    'within' => 'form',              // closest ancestor selector from which $target is searched
    'class' => '',
])
{{--
    Speech-to-text button (window.grigliaMic): appends what you say to the nearest field.
    While recording it shows the elapsed time, turns amber when the microphone hears nothing and keeps
    the error until you tap again (a failed transcription is retried, the audio is not thrown away).
    Hidden when the browser can neither record nor recognise speech.
--}}
<button
    type="button"
    x-data="grigliaMic(() => ($el.closest(@js($within)) || document).querySelector(@js($target)))"
    x-show="supported"
    x-cloak
    x-on:click="toggle()"
    x-bind:class="{ 'db-mic-on': on, 'db-mic-busy': busy, 'db-mic-error': error, 'db-mic-warn': silent }"
    x-bind:title="hint"
    x-bind:aria-label="hint"
    x-bind:aria-pressed="on ? 'true' : 'false'"
    x-bind:aria-busy="busy ? 'true' : 'false'"
    x-bind:disabled="busy"
    aria-label="{{ __('griglia::t.mic_start') }}"
    {{ $attributes->merge(['class' => 'db-mic cursor-pointer '.$class]) }}
><x-griglia::icon name="mic" /><span x-show="on" x-cloak x-text="clock" class="db-mic-time tabular-nums"></span></button>
