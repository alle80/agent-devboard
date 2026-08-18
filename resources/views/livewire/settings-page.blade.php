<div class="mx-auto w-full max-w-xl px-4 pt-24 pb-16 sm:pt-24" style="{{ $skin['vars'] }}">
    <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }}">⚙️ {{ __('devboard::t.settings_title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }}">{{ __('devboard::t.back_to_list') }}</a>
    </div>

    @foreach ($sections as $group => [$title, $intro, $fields])
        <section class="{{ $skin['card'] }} mb-6" aria-labelledby="sec-{{ $group }}">
            <h2 id="sec-{{ $group }}" class="{{ $skin['h2'] }}">{{ $title }}</h2>
            <p class="{{ $skin['sub'] }} mb-3">{{ $intro }} {{ __('devboard::t.settings_saves') }}</p>

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
                                <span class="sr-only">{{ $values[$group][$key] ? __('devboard::t.yes') : __('devboard::t.no') }}</span>
                            </button>
                        @elseif ($type === 'select')
                            <select
                                id="{{ $id }}"
                                wire:model.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-full sm:mt-1 sm:w-auto sm:max-w-[55%]"
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
                                wire:model.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-28 text-right sm:mt-1"
                            >
                        @elseif ($type === 'time')
                            <input
                                id="{{ $id }}"
                                type="time"
                                wire:model.change="values.{{ $group }}.{{ $key }}"
                                class="setting-input {{ $skin['input'] }} w-36 sm:mt-1"
                            >
                        @else
                            <input
                                id="{{ $id }}"
                                type="text"
                                wire:model.change="values.{{ $group }}.{{ $key }}"
                                placeholder="{{ __('devboard::t.settings_empty_default') }}"
                                autocomplete="off"
                                class="setting-input {{ $skin['input'] }} w-full sm:mt-1 sm:w-48"
                            >
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    {{-- Theme packs --}}
    <section class="{{ $skin['card'] }} mb-6" aria-labelledby="sec-themes" x-data="{ uploading: false }">
        <h2 id="sec-themes" class="{{ $skin['h2'] }}">{{ __('devboard::t.themes.title') }}</h2>
        <p class="{{ $skin['sub'] }} mb-3">{{ __('devboard::t.themes.intro') }}</p>

        <ul class="{{ $skin['divide'] }}">
            @forelse ($installedThemes as $slug => $th)
                <li class="flex items-center justify-between gap-3 py-2" wire:key="theme-{{ $slug }}">
                    <div class="min-w-0 flex-1">
                        <a href="{{ \Alle80\Devboard\Themes::url($slug) }}" class="{{ $skin['label'] }} hover:underline"><x-devboard::theme-icon :theme="$th" /> {{ $th['label'] }}</a>
                        <p class="{{ $skin['help'] }}">{{ $slug }}{{ ! empty($th['version']) ? ' · v'.$th['version'] : '' }}{{ ! empty($th['author']) ? ' · '.$th['author'] : '' }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="uninstallTheme('{{ $slug }}')"
                        wire:confirm="{{ __('devboard::t.themes.uninstall_confirm', ['label' => $th['label']]) }}"
                        class="{{ $skin['back'] }} shrink-0 text-sm"
                    >✕ {{ __('devboard::t.themes.uninstall') }}</button>
                </li>
            @empty
                <li class="{{ $skin['help'] }} py-2 italic">{{ __('devboard::t.themes.none') }}</li>
            @endforelse
        </ul>

        <label class="{{ $skin['back'] }} mt-3 inline-flex cursor-pointer items-center gap-2 text-sm">
            <span x-show="!uploading">📦 {{ __('devboard::t.themes.upload') }}</span>
            <span x-show="uploading" x-cloak>{{ __('devboard::t.themes.uploading') }}</span>
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
        <p class="{{ $skin['help'] }} mt-2 text-xs">{{ __('devboard::t.themes.how') }}</p>
    </section>

    <p class="{{ $skin['help'] }} text-center">{{ __('devboard::t.settings_footer') }}</p>
</div>
