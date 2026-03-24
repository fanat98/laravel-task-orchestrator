<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskProgressUpdater
{
    public function __construct(
        private readonly CurrentTaskRunStore $store,
    ) {
    }

    public function set(int $current, ?int $total = null, ?string $message = null): void
    {
        $taskRunId = $this->store->get();

        if ($taskRunId === null) {
            return;
        }

        TaskRunRecord::query()
            ->whereKey($taskRunId)
            ->update([
                'progress_current' => $current,
                'progress_total' => $total,
                'progress_message' => $message,
            ]);
    }

    public function clear(): void
    {
        $taskRunId = $this->store->get();

        if ($taskRunId === null) {
            return;
        }

        TaskRunRecord::query()
            ->whereKey($taskRunId)
            ->update([
                'progress_current' => null,
                'progress_total' => null,
                'progress_message' => null,
            ]);
    }
}
