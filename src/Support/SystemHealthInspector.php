<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Support\Facades\DB;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Throwable;

final class SystemHealthInspector
{
    /**
     * @return array{
     *     status: string,
     *     pending_jobs: int|null,
     *     stale_queued_runs: int,
     *     message: string
     * }
     */
    public function inspect(): array
    {
        $pendingJobs = $this->getPendingJobsCount();
        $staleQueuedRuns = $this->getStaleQueuedRunsCount();

        if ($staleQueuedRuns > 0) {
            return [
                'status' => 'critical',
                'pending_jobs' => $pendingJobs,
                'stale_queued_runs' => $staleQueuedRuns,
                'message' => 'Queued task runs are waiting too long. The queue worker may be stopped or blocked.',
            ];
        }

        if (($pendingJobs ?? 0) > 0) {
            return [
                'status' => 'warning',
                'pending_jobs' => $pendingJobs,
                'stale_queued_runs' => $staleQueuedRuns,
                'message' => 'There are pending queue jobs. The system may be processing normally or building backlog.',
            ];
        }

        return [
            'status' => 'healthy',
            'pending_jobs' => $pendingJobs,
            'stale_queued_runs' => $staleQueuedRuns,
            'message' => 'Queue and task execution look healthy.',
        ];
    }

    private function getStaleQueuedRunsCount(): int
    {
        return TaskRunRecord::query()
            ->where('status', TaskRunStatus::Queued->value)
            ->where('created_at', '<=', now()->subMinute())
            ->count();
    }

    private function getPendingJobsCount(): ?int
    {
        try {
            return DB::table('jobs')->count();
        } catch (Throwable) {
            return null;
        }
    }
}
