{{--
    Select dell'agente di un task (solo in multi-agente): «vuoto» = eredita l'agente della lista.
    Usata sia nella riga della lista sia fra i comandi del modale, così il gesto è lo stesso nei due posti.
    Variabili attese:
      $todo           il todo
      $change         espressione per wire:change (es. "setTodoAgent(12, $event.target.value)")
      $inheritLabel   testo dell'opzione «eredita» (nella riga è il nome dell'agente effettivo: il badge si vede sempre)
      $class          classi aggiuntive del select (opzionale)
--}}
<select
    class="db-agent-select cursor-pointer bg-transparent {{ $class ?? '' }}"
    wire:change="{{ $change }}"
    title="{{ __('griglia::t.agent_of_task') }}"
    aria-label="{{ __('griglia::t.agent_of_task') }}"
    x-on:click.stop
>
    <option value="" @selected(! $todo->agent)>{{ $inheritLabel }}</option>
    @foreach (\Alle80\Griglia\Agent::all() as $k => $label)
        <option value="{{ $k }}" @selected($todo->agent === $k)>{{ $label }}</option>
    @endforeach
</select>
