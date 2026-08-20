@props(['size' => '1em', 'class' => ''])

{{-- Brand mark di Griglia (griglia di task + pallino agente + spunta) inline in currentColor:
     eredita il colore del tema (guideline: in monocromia il glifo resta leggibile senza il verde). --}}
<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"
     style="height: {{ $size }}; width: {{ $size }};" {{ $attributes->merge(['class' => 'inline-block align-[-0.15em] '.$class]) }}>
    <g fill="none" stroke="currentColor" stroke-width="38" stroke-linecap="round" stroke-linejoin="round">
        <path d="M92 126 H382 V362 H92 Z"/>
        <path d="M188 126 V362 M284 126 V362"/>
        <path d="M92 244 H382"/>
    </g>
    <path d="M174 307 l35 35 70-82" fill="none" stroke="currentColor" stroke-width="36" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="397" cy="245" r="48" fill="currentColor"/>
</svg>
