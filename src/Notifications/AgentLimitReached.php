<?php

namespace Alle80\Griglia\Notifications;

use Alle80\Griglia\Models\Todo;
use Carbon\CarbonImmutable;

/** A plan usage window reached 100%; linked to the task currently held by that agent. */
class AgentLimitReached extends GrigliaNotification
{
    public function __construct(Todo $todo, public string $agentName, public string $windowLabel, public ?string $resetsAt)
    {
        parent::__construct($todo);
    }

    public function kind(): string
    {
        return 'agent_limit_reached';
    }

    public function icon(): string
    {
        return '⚠';
    }

    public function title(): string
    {
        return __('griglia::t.notif.limit_title', ['agent' => $this->agentName]);
    }

    public function body(): string
    {
        $reset = $this->resetsAt
            ? CarbonImmutable::parse($this->resetsAt)->timezone(config('app.timezone'))->format('d/m H:i')
            : __('griglia::t.notif.limit_reset_unknown');

        return __('griglia::t.notif.limit_body', ['window' => $this->windowLabel, 'reset' => $reset]);
    }
}
