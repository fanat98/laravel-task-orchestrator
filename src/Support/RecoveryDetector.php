<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class RecoveryDetector
{
    /**
     * Determine if the given successful run represents a recovery.
     * Returns the previous failed TaskRunRecord if recovery, null otherwise.
     */
    public function detect(TaskRunRecord $currentRun): ?TaskRunRecord
    {
        $previousRun = TaskRunRecord::query()
            ->where('task_name', $currentRun->task_name)
            ->where('id', '!=', $currentRun->id)
            ->whereIn('status', [
                TaskRunStatus::Succeeded->value,
                TaskRunStatus::Failed->value,
                TaskRunStatus::Cancelled->value,
            ])
            ->orderByDesc('finished_at')
            ->first();

        if ($previousRun === null) {
            return null;
        }

        if ($previousRun->status === TaskRunStatus::Failed->value) {
            return $previousRun;
        }

        return null;
    }
}
