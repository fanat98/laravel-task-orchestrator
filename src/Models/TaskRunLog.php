<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Models;

final class TaskRunLog extends OrchestratorModel
{
    protected $table = 'task_run_logs';

    protected $guarded = [];
}
