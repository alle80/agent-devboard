{{--
    Styles + scripts of the package, in one of two modes (config devboard.assets):
      'vite'        — the host app bundles the package CSS/JS in its own Vite build (default; see README)
      'precompiled' — the files built by the package (public/build, published to public/vendor/devboard/build)
    Also prints the runtime Echo configuration (window.DEVBOARD_ECHO) when a broadcaster key is set.
--}}
@php($mode = config('devboard.assets', 'vite'))
@php($echo = array_filter((array) config('devboard.echo', [])))
@if (! empty($echo['key']))
    <script>window.DEVBOARD_ECHO = @json($echo);</script>
@endif
@if (auth()->check() && \Illuminate\Support\Facades\Route::has('devboard.push.store'))
    @php($push = ['key' => (string) config('webpush.vapid.public_key', ''), 'subscribeUrl' => route('devboard.push.store'), 'testUrl' => route('devboard.notifications.test'), 'sw' => route('devboard.sw', absolute: false), 'csrf' => csrf_token()])
    <script>window.DEVBOARD_PUSH = {!! json_encode($push) !!};</script>
@endif
@if ($mode === 'precompiled')
    @php($base = rtrim((string) config('devboard.assets_url', '/vendor/devboard/build'), '/'))
    @php($v = @filemtime(public_path(ltrim($base, '/').'/devboard.css')) ?: '1')
    <link rel="stylesheet" href="{{ $base }}/devboard.css?v={{ $v }}">
    <script type="module" src="{{ $base }}/devboard.js?v={{ $v }}"></script>
@else
    @vite((array) config('devboard.vite_entries', ['resources/css/app.css', 'resources/js/app.js']))
@endif
