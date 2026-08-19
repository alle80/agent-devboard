{{--
    Skills of the coding agent for this task: an accordion (under the Task note) with one checkbox per
    skill of the catalogue (`devboard:skills-import`); the chosen ones are saved in `todos.skills` and
    printed by `devboard:check`, so the agent invokes them while working on the task.
    Expected: $todo, $skills (catalogue), $readonly; style classes $boxClass, $labelClass, $textClass.
--}}
@php($chosen = array_values((array) $todo->skills))
@php($catalogue = $skills)
@foreach ($chosen as $name)
    @php($catalogue[$name] ??= ['name' => $name, 'description' => '', 'source' => ''])
@endforeach
@if ($catalogue)
    <details class="{{ $boxClass }} db-skills" wire:key="skills-{{ $todo->id }}" x-data="{ o: {{ $chosen ? 'true' : 'false' }} }" x-bind:open="o" x-on:toggle="o = $el.open">
        <summary class="flex cursor-pointer items-center justify-between gap-2 select-none">
            <span class="{{ $labelClass }}">🧩 {{ __('devboard::t.skills') }}</span>
            <span class="{{ $textClass }} text-xs opacity-70">{{ $chosen ? __('devboard::t.skills_chosen', ['count' => count($chosen)]) : __('devboard::t.skills_none') }}</span>
        </summary>
        <p class="{{ $textClass }} mt-1 text-xs opacity-60">{{ __('devboard::t.skills_hint') }}</p>
        <ul class="mt-2 space-y-1.5" role="list">
            @foreach ($catalogue as $name => $sk)
                @php($on = in_array($name, $chosen, true))
                <li wire:key="skill-{{ $todo->id }}-{{ md5($name) }}">
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
