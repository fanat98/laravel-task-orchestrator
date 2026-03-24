<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Models;

use Illuminate\Database\Eloquent\Model;

abstract class OrchestratorModel extends Model
{
    public function getConnectionName(): ?string
    {
        return config('task-orchestrator.database_connection');
    }
}
