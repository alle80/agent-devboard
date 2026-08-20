<?php

namespace Alle80\Griglia\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Settings that drive how the coding agent works on the agent list (config griglia.agent_list).
 * Edited from /settings; `griglia:check` prints them first so the agent follows them.
 * To add one: property here + default in a settings migration + entry in fields() and in the translations.
 */
class AgentSettings extends Settings
{
    /** Commit automatico alla chiusura di ogni task. */
    public bool $commit_after_task;

    /** Push su GitHub dopo il commit automatico (se spento: commit sì, push solo su richiesta). */
    public bool $push_after_commit;

    /** 'ask' = fa domande ❓ quando in dubbio; 'decide' = decide da solo e spiega nel commento 🤖. */
    public string $autonomy;

    /** Notifica push sul telefono quando chiude un task. */
    public bool $notify_on_done;

    /** Notifica push sul telefono quando pone una domanda ❓. */
    public bool $notify_on_question;

    /** Prima di chiudere: screenshot mobile+desktop e test Livewire automatici. */
    public bool $verify_before_close;

    /** 'short' = commento 🤖 essenziale; 'detailed' = con dettagli tecnici e come provare. */
    public string $comment_detail;

    /** 'main' = commit diretti su main; 'branch_pr' = branch per task + Pull Request su GitHub. */
    public string $git_flow;

    /** Riepilogo serale via push (cosa è stato chiuso in giornata). */
    public bool $daily_summary;

    /** Ora del riepilogo serale (HH:MM). */
    public string $daily_summary_time;

    /** Alla chiusura di un task spunta automaticamente tutti i sotto-task. */
    public bool $check_subtasks_on_done;

    /** 'ordered' = un task alla volta, in ordine; 'multitasking' = più task in parallelo (con cautela). */
    public string $task_mode;

    public static function group(): string
    {
        return 'agent';
    }

    /**
     * Definizione dei campi per la pagina impostazioni e per sviluppo:check.
     * chiave => [label, help, type (bool|select|int|time), options (per select: valore => etichetta)]
     */
    /**
     * Fields for the settings page and griglia:check.
     * key => [label, help, type (bool|select|int|text|time), options (select: value => label)]
     * Labels/help/options come from the translations (griglia::t.settings_fields / settings_options).
     */
    public static function fields(): array
    {
        $types = [
            'commit_after_task' => 'bool', 'push_after_commit' => 'bool', 'autonomy' => 'select', 'notify_on_done' => 'bool',
            'notify_on_question' => 'bool', 'verify_before_close' => 'bool', 'comment_detail' => 'select', 'git_flow' => 'select',
            'daily_summary' => 'bool', 'daily_summary_time' => 'time', 'check_subtasks_on_done' => 'bool',
            'task_mode' => 'select',
        ];
        $labels = (array) __('griglia::t.settings_fields');
        $options = (array) __('griglia::t.settings_options');
        $out = [];
        foreach ($types as $key => $type) {
            [$label, $help] = $labels[$key] ?? [$key, ''];
            $out[$key] = [$label, $help, $type, $type === 'select' ? ($options[$key] ?? []) : []];
        }

        return $out;
    }

    /** Compact one-liner for griglia:check. */
    public function summary(): string
    {
        $out = [];
        foreach (self::fields() as $key => $f) {
            $v = $this->{$key};
            $out[] = $f[0].': '.match ($f[2]) {
                'bool' => $v ? __('griglia::t.yes') : __('griglia::t.no'),
                'select' => $f[3][$v] ?? $v,
                default => (string) $v,
            };
        }

        return implode(' · ', $out);
    }
}
