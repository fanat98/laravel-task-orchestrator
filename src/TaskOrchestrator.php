<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator;

final class TaskOrchestrator
{
    public function ping(): string
    {
        return 'task-orchestrator loaded';
    }
}
