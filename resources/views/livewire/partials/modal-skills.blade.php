{{--
    Skills of the coding agent for this task: an accordion (under the Task note) with one checkbox per
    skill of the catalogue (`griglia:skills-import`); the chosen ones are saved in `todos.skills` and
    printed by `griglia:check`, so the agent invokes them while working on the task.
    Expected: $todo, $skills (catalogue), $readonly; style classes $boxClass, $labelClass, $textClass.
--}}
@php($chosen = array_values((array) $todo->skills))
@php($catalogue = $skills)
@foreach ($chosen as $name)
    @php($catalogue[$name] ??= ['name' => $name, 'description' => '', 'source' => ''])
@endforeach
@php(uksort($catalogue, fn ($a, $b) => [! in_array($a, $chosen, true), strtolower($a)] <=> [! in_array($b, $chosen, true), strtolower($b)]))
@if ($catalogue)
    <details class="{{ $boxClass }} db-skills" wire:key="skills-{{ $todo->id }}" x-data="{ o: {{ $chosen ? 'true' : 'false' }}, q: '', hay: @js(array_values(array_map(fn ($sk) => $sk['name'].' '.($sk['description'] ?? '').' '.($sk['source'] ?? ''), $catalogue))), match(t) { return this.q.trim() === '' || t.toLowerCase().includes(this.q.trim().toLowerCase()); }, noMatch() { return this.q.trim() !== '' && ! this.hay.some(t => this.match(t)); } }" x-bind:open="o" x-on:toggle="o = $el.open">
        <summary class="flex cursor-pointer items-center justify-between gap-2 select-none">
            <span class="{{ $labelClass }} inline-flex items-center gap-1"><x-griglia::icon name="puzzle" /> {{ __('griglia::t.skills', ['agent' => \Alle80\Griglia\Agent::name()]) }}</span>
            <span class="{{ $textClass }} text-xs opacity-70">{{ $chosen ? __('griglia::t.skills_chosen', ['count' => count($chosen)]) : __('griglia::t.skills_none') }}</span>
        </summary>
        <p class="{{ $textClass }} mt-1 text-xs opacity-60">{{ __('griglia::t.skills_hint') }}</p>
        {{-- Live search (client-side, Alpine): filters the list below while typing --}}
        <input
            type="search"
            x-model="q"
            x-on:keydown.escape.stop="q = ''"
            placeholder="{{ __('griglia::t.skills_search') }}"
            aria-label="{{ __('griglia::t.skills_search') }}"
            class="tl-input db-skills-search mt-2 w-full px-3 py-1.5 text-sm focus:outline-none"
        >
        <p class="{{ $textClass }} mt-1 text-xs opacity-60" x-show="noMatch()" x-cloak>{{ __('griglia::t.skills_no_match') }}</p>
        <ul class="mt-2 space-y-1.5" role="list">
            @foreach ($catalogue as $name => $sk)
                @php($on = in_array($name, $chosen, true))
                <li wire:key="skill-{{ $todo->id }}-{{ md5($name) }}" x-show="match(@js($name.' '.($sk['description'] ?? '').' '.($sk['source'] ?? '')))" x-bind:hidden="!match(@js($name.' '.($sk['description'] ?? '').' '.($sk['source'] ?? '')))">
                    <label class="flex cursor-pointer items-start gap-2 {{ $readonly && ! $on ? 'opacity-50' : '' }}">
                        <input
                            type="checkbox"
                            class="db-skill-check mt-1 shrink-0"
                            @checked($on)
                            @disabled($readonly)
                            wire:click="toggleSkill(@js($name))"
                            aria-label="{{ $name }}"
                        >
                        <span class="min-w-0">
                            <span class="font-bold {{ $on ? '' : 'opacity-85' }}">{{ $name }}</span>
                            @if ($sk['source'] ?? '')<span class="ml-1 text-xs opacity-50">{{ $sk['source'] }}</span>@endif
                            @if ($sk['description'] ?? '')<span class="{{ $textClass }} block text-xs opacity-70">{{ \Illuminate\Support\Str::limit($sk['description'], 160) }}</span>@endif
                        </span>
                    </label>
                </li>
            @endforeach
        </ul>
    </details>
@endif
