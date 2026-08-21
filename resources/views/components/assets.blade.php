{{--
    Styles + scripts of the package, in one of two modes (config griglia.assets):
      'vite'        — the host app bundles the package CSS/JS in its own Vite build (default; see README)
      'precompiled' — the files built by the package (public/build, published to public/vendor/griglia/build)
    Also prints the runtime Echo configuration (window.GRIGLIA_ECHO) when a broadcaster key is set.
--}}
@php($mode = config('griglia.assets', 'vite'))
@php($echo = array_filter((array) config('griglia.echo', [])))
@if (! empty($echo['key']))
    <script>window.GRIGLIA_ECHO = @json($echo);</script>
@endif
{{-- Etichette usate dal JS (pulsante «copia» dei blocchi di codice) --}}
@php($i18n = ['copy' => __('griglia::t.copy'), 'copied' => __('griglia::t.copied'), 'copy_failed' => __('griglia::t.copy_failed'), 'copy_block' => __('griglia::t.copy_block')])
<script>window.GRIGLIA_I18N = @json($i18n);</script>
@if (\Illuminate\Support\Facades\Route::has('griglia.transcribe'))
    @php($speech = \Alle80\Griglia\Support\Speech::frontend())
    <script>window.GRIGLIA_SPEECH = @json($speech);</script>
@endif
@if (auth()->check() && \Illuminate\Support\Facades\Route::has('griglia.push.store'))
    @php($push = ['key' => (string) config('webpush.vapid.public_key', ''), 'subscribeUrl' => route('griglia.push.store'), 'testUrl' => route('griglia.notifications.test'), 'sw' => route('griglia.sw', absolute: false), 'csrf' => csrf_token()])
    <script>window.GRIGLIA_PUSH = @json($push);</script>
@endif
@if ($mode === 'precompiled')
    @php($base = rtrim((string) config('griglia.assets_url', '/vendor/griglia/build'), '/'))
    @php($v = @filemtime(public_path(ltrim($base, '/').'/griglia.css')) ?: '1')
    <link rel="stylesheet" href="{{ $base }}/griglia.css?v={{ $v }}">
    <script type="module" src="{{ $base }}/griglia.js?v={{ $v }}"></script>
@else
    @vite((array) config('griglia.vite_entries', ['resources/css/app.css', 'resources/js/app.js']))
@endif
