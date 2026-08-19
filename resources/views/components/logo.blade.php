@props(['size' => '1em', 'class' => ''])

{{-- Brand mark («D with Check & Dot») inline in currentColor: eredita il colore del tema (guideline: mai il punto colorato nei contesti monocolore). --}}
<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"
     style="height: {{ $size }}; width: {{ $size }};" {{ $attributes->merge(['class' => 'inline-block align-[-0.15em] '.$class]) }}>
    <path d="M112 92 H252 C342 92 402 157 402 256 C402 344 352 401 278 417 M112 92 V420 H258"
          fill="none" stroke="currentColor" stroke-width="58" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M181 256 L232 307 L323 207"
          fill="none" stroke="currentColor" stroke-width="48" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="391" cy="397" r="43" fill="currentColor"/>
</svg>
