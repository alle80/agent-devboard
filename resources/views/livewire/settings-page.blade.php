{{-- Una sola colonna a ogni larghezza (task 329): il contenitore resta stretto quanto basta
     a leggere le voci; su desktop si allarga solo per fare posto all'indice laterale. --}}
<div class="mx-auto w-full max-w-xl px-4 pt-24 pb-16 sm:pt-24 lg:max-w-4xl xl:max-w-5xl xl:px-8" style="{{ $skin['vars'] }}" x-data="{ tab: 'agent' }">
    <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} inline-flex items-center gap-2"><x-griglia::icon name="settings" size="1em" /> {{ __('griglia::t.settings_title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>

    @php($icons = ['agent' => 'bot', 'optimization' => 'bolt', 'app' => 'board'])
    @php($tabs = [])
    @foreach ($sections as $group => [$title, $intro, $fields])
        @php($tabs[] = ['key' => $group, 'label' => $title, 'icon' => $icons[$group] ?? 'board', 'count' => count($fields)])
    @endforeach
    @unless (\Alle80\Griglia\Mode::isLocal())
        @php($tabs[] = ['key' => 'notif', 'label' => __('griglia::t.notif.title'), 'icon' => 'bell', 'count' => null])
    @endunless
    @php($tabs[] = ['key' => 'themes', 'label' => __('griglia::t.themes.title'), 'icon' => 'palette', 'count' => null])

    {{-- Un gruppo alla volta a ogni larghezza (task 329): su desktop l'indice sta a sinistra,
         sotto lg diventa una striscia di schede scorrevole sopra il pannello. --}}
    <nav class="-mx-4 mb-4 overflow-x-auto px-4 pb-1 lg:hidden" aria-label="{{ __('griglia::t.settings_title') }}">
        <ul class="flex w-max gap-2">
            @foreach ($tabs as $t)
                <li>
                    <button
                        type="button"
                        x-on:click="tab = '{{ $t['key'] }}'"
                        x-bind:class="tab === '{{ $t['key'] }}' ? 'tl-btn-on' : ''"
                        x-bind:aria-current="tab === '{{ $t['key'] }}' ? 'true' : 'false'"
                        aria-controls="panel-{{ $t['key'] }}"
                        class="tl-btn tl-btn-sm shrink-0 whitespace-nowrap"
                    >
                        <x-griglia::icon :name="$t['icon']" size="1em" />
                        <span>{{ $t['label'] }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="lg:grid lg:grid-cols-[13rem_1fr] lg:items-start lg:gap-6">

        <nav class="hidden lg:sticky lg:top-24 lg:block" aria-label="{{ __('griglia::t.settings_title') }}">
            <ul class="space-y-1">
                @foreach ($tabs as $t)
                    <li>
                        <button
                            type="button"
                            x-on:click="tab = '{{ $t['key'] }}'"
                            x-bind:class="tab === '{{ $t['key'] }}' ? 'tl-btn-on' : ''"
                            x-bind:aria-current="tab === '{{ $t['key'] }}' ? 'true' : 'false'"
                            aria-controls="panel-{{ $t['key'] }}"
                            class="tl-btn tl-btn-sm w-full justify-start text-left"
                        >
                            <x-griglia::icon :name="$t['icon']" size="1em" />
                            <span class="min-w-0 flex-1 truncate">{{ $t['label'] }}</span>
                            @if ($t['count'])<span class="text-[11px] tabular-nums opacity-60">{{ $t['count'] }}</span>@endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="min-w-0">

    @foreach ($sections as $group => [$title, $intro, $fields])
        <section id="panel-{{ $group }}" class="{{ $skin['card'] }} mb-6" aria-labelledby="sec-{{ $group }}" x-bind:class="tab === '{{ $group }}' ? '' : 'hidden'">
            <h2 id="sec-{{ $group }}" class="{{ $skin['h2'] }} inline-flex items-center gap-2"><x-griglia::icon :name="['agent' => 'bot', 'optimization' => 'bolt', 'app' => 'board'][$group] ?? 'board'" size="1em" /> {{ $title }}</h2>
            <p class="{{ $skin['sub'] }} mb-3">{{ $intro }} {{ __('griglia::t.settings_saves') }}</p>

            <ul class="{{ $skin['divide'] }}">
                @foreach ($fields as $key => $f)
                    @php([$label, $help, $type] = $f)
                    @php($opts = $f[3] ?? [])
                    @php($id = "s-{$group}-{$key}")
                    <li class="flex gap-3 py-3 {{ $type === 'bool' ? 'flex-row items-start justify-between' : 'flex-col sm:flex-row sm:items-start sm:justify-between sm:gap-4' }}" wire:key="setting-{{ $group }}-{{ $key }}">
                        <div class="min-w-0 flex-1">
                            <label for="{{ $id }}" class="{{ $skin['label'] }}">{{ $label }}</label>
                            <p class="{{ $skin['help'] }}">{{ $help }}</p>
                        </div>

                        {{-- I campi non-bool salvano da soli sull'evento «change» (task 436): in Livewire 4
                             «.change» da solo aggiorna il valore solo lato client, serve «.live» perché
                             parta la richiesta e scatti updatedValues() → save(). --}}
                        @if ($type === 'bool')
                            <button
                                type="button"
                                id="{{ $id }}"
                                role="switch"
                                aria-checked="{{ $values[$group][$key] ? 'true' : 'false' }}"
                                aria-label="{{ $label }}"
                                wire:click="toggle('{{ $group }}', '{{ $key }}')"
                                class="setting-switch mt-1 {{ $values[$group][$key] ? 'is-on' : '' }}"
                            >
                                <span class="setting-knob"></span>
                                <span class="sr-only">{{ $values[$group][$key] ? __('griglia::t.yes') : __('griglia::t.no') }}</span>
                            </button>
                        @elseif ($type === 'select')
                            <select
                                id="{{ $id }}"
                                wire:model.live.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-full sm:mt-1 sm:w-auto sm:max-w-[55%] sm:min-w-[10rem] lg:max-w-[60%]"
                            >
                                @foreach ($opts as $v => $lbl)
                                    <option value="{{ $v }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        @elseif ($type === 'int')
                            <input
                                id="{{ $id }}"
                                type="number"
                                inputmode="numeric"
                                min="{{ $opts['min'] ?? 0 }}" max="{{ $opts['max'] ?? 9999 }}"
                                wire:model.live.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-28 text-right sm:mt-1"
                            >
                        @elseif ($type === 'time')
                            <input
                                id="{{ $id }}"
                                type="time"
                                wire:model.live.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-36 sm:mt-1"
                            >
                        @else
                            <input
                                id="{{ $id }}"
                                type="text"
                                wire:model.live.change="values.{{ $group }}.{{ $key }}"
                                placeholder="{{ __('griglia::t.settings_empty_default') }}"
                                autocomplete="off"
                                class="setting-input {{ $skin['input'] }} w-full sm:mt-1 sm:w-48"
                            >
                        @endif
                    </li>
                    @if ($key === 'mode')
                        <p class="db-setting-warn" x-data x-show="$wire.get('values.app.mode') === 'local'" x-cloak>{{ __('griglia::t.settings_options.mode_warn') }}</p>
                    @endif
                    @if ($key === 'task_mode')
                        {{-- Reso sempre, mostrato via Alpine quando è "multitasking": la sola @if lato
                             server non veniva inserita dal morph di Livewire al cambio della select. --}}
                        <li class="pb-3" wire:key="warn-{{ $group }}-{{ $key }}"
                            x-data x-show="$wire.get('values.{{ $group }}.{{ $key }}') === 'multitasking'" x-cloak>
                            <p class="db-setting-warn">{{ __('griglia::t.settings_options.task_mode_warn') }}</p>
                        </li>
                    @endif
                @endforeach
            </ul>
        </section>
    @endforeach

    {{-- Web Push on this device (the channel toggles live in the App group above) --}}
    @unless (\Alle80\Griglia\Mode::isLocal())
    <section
        id="panel-notif"
        class="{{ $skin['card'] }} mb-6"
        aria-labelledby="sec-notif"
        x-bind:class="tab === 'notif' ? '' : 'hidden'"
        x-data="{
            st: 'checking', busy: false, sent: false, diag: null, log: [],
            async refresh() { this.st = window.grigliaPush ? await window.grigliaPush.status() : 'unsupported'; this.diag = window.grigliaPush ? await window.grigliaPush.diagnose() : null; window.grigliaPush && window.grigliaPush.onPush((d) => { this.log.unshift(@js(__('griglia::t.notif.diag_received')) + ' «' + d.title + '»'); }); },
            async localTest() { this.busy = true; try { await window.grigliaPush.localTest(@js(__('griglia::t.notif.diag_local_title')), @js(__('griglia::t.notif.diag_local_body'))); this.log.unshift(@js(__('griglia::t.notif.diag_local_sent'))); } catch (e) { this.log.unshift('✗ ' + e.message); } this.busy = false; },
            async enable() { this.busy = true; try { this.st = await window.grigliaPush.enable(); } catch (e) { console.error(e); } this.busy = false; },
            async disable() { this.busy = true; try { this.st = await window.grigliaPush.disable(); } catch (e) { console.error(e); } this.busy = false; },
            async test() { this.busy = true; this.sent = false; try { this.sent = await window.grigliaPush.test(); this.log.unshift(@js(__('griglia::t.notif.diag_server_sent'))); } catch (e) { console.error(e); } this.busy = false; },
        }"
        x-init="refresh()"
    >
        <h2 id="sec-notif" class="{{ $skin['h2'] }} inline-flex items-center gap-2"><x-griglia::icon name="bell" size="1em" /> {{ __('griglia::t.notif.section_title') }}</h2>
        <p class="{{ $skin['sub'] }} mb-3">{{ __('griglia::t.notif.section_intro') }}</p>
        <p class="{{ $skin['help'] }} mb-2" x-cloak>
            <span x-show="st === 'on'" class="inline-flex items-center gap-1"><x-griglia::icon name="check" /> {{ __('griglia::t.notif.device_on') }}</span>
            <span x-show="st === 'off'">{{ __('griglia::t.notif.device_off') }}</span>
            <span x-show="st === 'denied'" class="inline-flex items-center gap-1"><x-griglia::icon name="ban" /> {{ __('griglia::t.notif.device_denied') }}</span>
            <span x-show="st === 'unsupported'">{{ __('griglia::t.notif.device_unsupported') }}</span>
            <span x-show="st === 'nokey'" class="inline-flex items-center gap-1"><x-griglia::icon name="alert" /> {{ __('griglia::t.notif.device_nokey') }}</span>
        </p>
        <div class="flex flex-wrap items-center gap-2" x-cloak>
            <button type="button" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm" x-show="st === 'off'" x-bind:disabled="busy" x-on:click="enable()"><x-griglia::icon name="bell" /> {{ __('griglia::t.notif.device_enable') }}</button>
            <button type="button" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm" x-show="st === 'on'" x-bind:disabled="busy" x-on:click="disable()"><x-griglia::icon name="bell-off" /> {{ __('griglia::t.notif.device_disable') }}</button>
            <button type="button" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm" x-bind:disabled="busy" x-on:click="test()"><x-griglia::icon name="send" /> {{ __('griglia::t.notif.test') }}</button>
            <span class="{{ $skin['help'] }} text-xs" x-show="sent">{{ __('griglia::t.notif.test_sent') }}</span>
            <button type="button" class="{{ $skin['back'] }} inline-flex items-center gap-1 text-sm" x-show="st === 'on'" x-bind:disabled="busy" x-on:click="localTest()"><x-griglia::icon name="bell" /> {{ __('griglia::t.notif.diag_local') }}</button>
        </div>
        {{-- Diagnostics: what this device really has (helps when pushes do not show up) --}}
        <details class="mt-3 text-xs" x-show="diag" x-cloak>
            <summary class="{{ $skin['help'] }} cursor-pointer select-none">{{ __('griglia::t.notif.diag_title') }}</summary>
            <dl class="{{ $skin['help'] }} mt-1 grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 tabular-nums">
                <dt>{{ __('griglia::t.notif.diag_permission') }}</dt><dd x-text="diag?.permission"></dd>
                <dt>{{ __('griglia::t.notif.diag_sw') }}</dt><dd x-text="diag?.registered ? 'OK' : 'NO'"></dd>
                <dt>{{ __('griglia::t.notif.diag_sub') }}</dt><dd x-text="diag?.subscribed ? ('OK (' + (diag.endpointHost || '?') + ')') : 'NO'"></dd>
                <dt>{{ __('griglia::t.notif.diag_mode') }}</dt><dd x-text="diag?.standalone ? 'PWA' : 'browser'"></dd>
                <dt>{{ __('griglia::t.notif.diag_server_subs') }}</dt><dd>{{ $pushSubscriptions }}</dd>
            </dl>
            <ul class="mt-1 space-y-0.5" x-show="log.length"><template x-for="(l, i) in log" :key="i"><li class="{{ $skin['help'] }}" x-text="l"></li></template></ul>
            <p class="{{ $skin['help'] }} mt-1">{{ __('griglia::t.notif.diag_hint') }}</p>
        </details>
    </section>
    @endunless

    {{-- Theme packs --}}
    <section id="panel-themes" class="{{ $skin['card'] }} mb-6" aria-labelledby="sec-themes" x-data="{ uploading: false }" x-bind:class="tab === 'themes' ? '' : 'hidden'">
        <h2 id="sec-themes" class="{{ $skin['h2'] }} inline-flex items-center gap-2"><x-griglia::icon name="palette" size="1em" /> {{ __('griglia::t.themes.title') }}</h2>
        <p class="{{ $skin['sub'] }} mb-3">{{ __('griglia::t.themes.intro') }}</p>

        <ul class="{{ $skin['divide'] }}">
            @forelse ($installedThemes as $slug => $th)
                <li class="flex items-center justify-between gap-3 py-2" wire:key="theme-{{ $slug }}">
                    <div class="min-w-0 flex-1">
                        <a href="{{ \Alle80\Griglia\Themes::url($slug) }}" class="{{ $skin['label'] }} hover:underline"><x-griglia::theme-icon :theme="$th" /> {{ $th['label'] }}</a>
                        <p class="{{ $skin['help'] }}">{{ $slug }}{{ ! empty($th['version']) ? ' · v'.$th['version'] : '' }}{{ ! empty($th['author']) ? ' · '.$th['author'] : '' }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="uninstallTheme('{{ $slug }}')"
                        wire:confirm="{{ __('griglia::t.themes.uninstall_confirm', ['label' => $th['label']]) }}"
                        class="{{ $skin['back'] }} shrink-0 text-sm"
                    ><x-griglia::icon name="close" /> {{ __('griglia::t.themes.uninstall') }}</button>
                </li>
            @empty
                <li class="{{ $skin['help'] }} py-2 italic">{{ __('griglia::t.themes.none') }}</li>
            @endforelse
        </ul>

        <label class="{{ $skin['back'] }} mt-3 inline-flex cursor-pointer items-center gap-2 text-sm">
            <span x-show="!uploading" class="inline-flex items-center gap-1"><x-griglia::icon name="package" /> {{ __('griglia::t.themes.upload') }}</span>
            <span x-show="uploading" x-cloak>{{ __('griglia::t.themes.uploading') }}</span>
            <input
                type="file"
                accept=".zip,application/zip"
                class="sr-only"
                wire:model="themeZip"
                x-on:livewire-upload-start="uploading = true"
                x-on:livewire-upload-finish="uploading = false"
                x-on:livewire-upload-error="uploading = false"
            >
        </label>
        <p class="{{ $skin['help'] }} mt-2 text-xs">{{ __('griglia::t.themes.how') }}</p>
    </section>

        </div>{{-- /pannelli --}}
    </div>{{-- /indice + pannelli --}}

    <p class="{{ $skin['help'] }} text-center">{{ __('griglia::t.settings_footer') }}</p>
</div>
