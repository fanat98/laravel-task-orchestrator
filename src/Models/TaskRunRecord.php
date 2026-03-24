<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class TaskRunRecord extends OrchestratorModel
{
    protected $table = 'task_runs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'command_arguments' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskRunLog::class, 'task_run_id', 'id')
            ->orderBy('id');
    }
}
