<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Illuminate\Console\Command;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;

final class RecoverStaleTaskRunsCommand extends Command
{
    protected $signature = 'task-orchestrator:recover-stale-runs {--minutes= : Override timeout for all tasks}';

    protected $description = 'Marks stale queued or running task runs as failed';

    public function __construct(
        private readonly TaskOrchestratorManager $tasks,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $globalOverrideMinutes = $this->option('minutes');
        $defaultMinutes = (int) config('task-orchestrator.stale_run_default_minutes', 10);
        $now = now();

        $runs = TaskRunRecord::query()
            ->whereIn('status', [
                TaskRunStatus::Queued->value,
                TaskRunStatus::Running->value,
            ])
            ->get();

        $recovered = 0;

        foreach ($runs as $run) {
            $timeoutSeconds = $this->resolveTimeoutSeconds($run, $globalOverrideMinutes, $defaultMinutes);
            $deadline = $this->resolveDeadline($run, $timeoutSeconds, $globalOverrideMinutes !== null);

            $isStale = $deadline !== null && $deadline->lessThanOrEqualTo($now);

            if (! $isStale) {
                continue;
            }

            $minutes = max((int) ceil($timeoutSeconds / 60), 1);

            $run->update([
                'status' => TaskRunStatus::Failed->value,
                'failure_message' => sprintf(
                    'Run was automatically marked as failed because it remained in status [%s] for more than %d minutes.',
                    $run->status,
                    $minutes
                ),
                'finished_at' => now(),
            ]);

            $this->warn(sprintf(
                'Recovered stale run [%s] for task [%s] using timeout [%d minutes].',
                $run->id,
                $run->task_name,
                $minutes
            ));

            $recovered++;
        }

        if ($recovered === 0) {
            $this->info('No stale runs found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Recovered %d stale run(s).', $recovered));

        return self::SUCCESS;
    }

    private function resolveTimeoutSeconds(TaskRunRecord $run, mixed $globalOverrideMinutes, int $defaultMinutes): int
    {
        if ($globalOverrideMinutes !== null) {
            return max((int) $globalOverrideMinutes * 60, 60);
        }

        if ($run->timeout_seconds !== null) {
            return max((int) $run->timeout_seconds, 60);
        }

        $task = $this->tasks->find($run->task_name);

        return max((int) (($task?->timeoutMinutes ?? $defaultMinutes) * 60), 60);
    }

    private function resolveDeadline(TaskRunRecord $run, int $timeoutSeconds, bool $hasGlobalOverride): ?\Illuminate\Support\Carbon
    {
        if (! $hasGlobalOverride && $run->timeout_at !== null) {
            return $run->timeout_at;
        }

        $anchor = $run->started_at ?? $run->created_at;

        if ($anchor === null) {
            return null;
        }

        return $anchor->copy()->addSeconds($timeoutSeconds);
    }
}
