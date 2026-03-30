<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TaskDetailDataProvider
{
    public function __construct(
        private readonly TaskOrchestratorManager $tasks,
        private readonly TaskStartabilityStateResolver $startabilityResolver,
    ) {
    }

    /**
     * @return array{task: array<string, mixed>, recent_runs: array<int, array<string, mixed>>}
     */
    public function taskDetail(string $taskName): array
    {
        $task = $this->requireTask($taskName);
        $relatedTaskNames = array_values(array_unique(array_merge([$task->name], $task->dependsOn)));
        $activeRunsByTask = $this->startabilityResolver->activeRunsByTaskName($relatedTaskNames);
        $latestRunsByTask = $this->startabilityResolver->latestRunsByTaskName($relatedTaskNames);
        $startabilityState = $this->startabilityResolver->stateFor($task, $activeRunsByTask, $latestRunsByTask);

        $latestRun = TaskRunRecord::query()
            ->where('task_name', $task->name)
            ->latest('started_at')
            ->latest('created_at')
            ->first();

        $recentRuns = TaskRunRecord::query()
            ->where('task_name', $task->name)
            ->latest('started_at')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (TaskRunRecord $run) => $this->mapRun($run))
            ->values()
            ->all();

        return [
            'task' => [
                'name' => $task->name,
                'label' => $task->label,
                'group' => $task->group,
                'queue' => $task->queue,
                'connection' => $task->connection,
                'timeout_minutes' => $task->timeoutMinutes,
                'depends_on' => $task->dependsOn,
                'allow_manual_run' => $task->allowManualRun,
                'is_queued' => $startabilityState['is_queued'],
                'is_running' => $startabilityState['is_running'],
                'is_blocked_by_dependencies' => $startabilityState['is_blocked_by_dependencies'],
                'blocked_by_task_names' => $startabilityState['blocked_by_task_names'],
                'start_block_reason' => $startabilityState['start_block_reason'],
                'can_start' => $startabilityState['can_start'],
                'last_status' => $latestRun?->status,
                'last_run_at' => $latestRun?->started_at?->toDateTimeString(),
            ],
            'recent_runs' => $recentRuns,
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int|null>}
     */
    public function runsTab(string $taskName, int $perPage = 20): array
    {
        $task = $this->requireTask($taskName);

        $runs = TaskRunRecord::query()
            ->where('task_name', $task->name)
            ->latest('started_at')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $runs->getCollection()
                ->map(fn (TaskRunRecord $run) => $this->mapRun($run))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int|null>}
     */
    public function failuresTab(string $taskName, int $perPage = 20): array
    {
        $task = $this->requireTask($taskName);

        $runs = TaskRunRecord::query()
            ->where('task_name', $task->name)
            ->where('status', TaskRunStatus::Failed->value)
            ->latest('started_at')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => $runs->getCollection()
                ->map(fn (TaskRunRecord $run) => $this->mapRun($run))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        ];
    }

    /**
     * @return array{selected_run_id: string|null, selected_run: array<string, mixed>|null, logs: array<int, array<string, mixed>>}
     */
    public function logsTab(string $taskName, ?string $runId = null): array
    {
        $task = $this->requireTask($taskName);

        $selectedRun = null;

        if ($runId !== null) {
            $selectedRun = TaskRunRecord::query()
                ->where('task_name', $task->name)
                ->where('id', $runId)
                ->with('logs')
                ->first();
        }

        if ($selectedRun === null) {
            $selectedRun = TaskRunRecord::query()
                ->where('task_name', $task->name)
                ->latest('started_at')
                ->latest('created_at')
                ->with('logs')
                ->first();
        }

        if ($selectedRun === null) {
            return [
                'selected_run_id' => null,
                'selected_run' => null,
                'logs' => [],
            ];
        }

        return [
            'selected_run_id' => $selectedRun->id,
            'selected_run' => [
                'id' => $selectedRun->id,
                'status' => $selectedRun->status,
                'started_at' => $selectedRun->started_at?->toDateTimeString(),
                'finished_at' => $selectedRun->finished_at?->toDateTimeString(),
            ],
            'logs' => $selectedRun->logs->map(fn ($log) => [
                'id' => $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{description: string|null, documentation: string|null}
     */
    public function documentationTab(string $taskName): array
    {
        $task = $this->requireTask($taskName);
        $command = $this->resolveCommand($task->command ?? '');

        $description = $task->description;

        if ($command !== null && method_exists($command, 'getDescription')) {
            $commandDescription = trim((string) $command->getDescription());

            if ($commandDescription !== '') {
                $description = $commandDescription;
            }
        }

        $documentation = null;

        if ($command !== null && method_exists($command, 'documentation')) {
            $value = $command->documentation();
            $documentation = is_string($value) && trim($value) !== '' ? $value : null;
        } elseif ($command !== null && property_exists($command, 'documentation')) {
            try {
                $reader = \Closure::bind(function () {
                    return $this->documentation ?? null;
                }, $command, $command);

                $value = is_callable($reader) ? $reader() : null;
                $documentation = is_string($value) && trim($value) !== '' ? $value : null;
            } catch (\Throwable) {
                $documentation = null;
            }
        }

        return [
            'description' => $description,
            'documentation' => $documentation,
        ];
    }

    /**
     * @return array{id: string, status: string, started_at: string|null, duration: int|null, trigger: string, finished_at: string|null, failure_message: string|null}
     */
    private function mapRun(TaskRunRecord $run): array
    {
        $duration = null;

        if ($run->started_at !== null && $run->finished_at !== null) {
            $duration = $run->started_at->diffInSeconds($run->finished_at);
        }

        return [
            'id' => $run->id,
            'status' => Str::of($run->status)->lower()->toString(),
            'started_at' => $run->started_at?->toDateTimeString(),
            'duration' => $duration,
            'trigger' => $run->trigger_type ?? 'manual',
            'finished_at' => $run->finished_at?->toDateTimeString(),
            'failure_message' => $run->failure_message,
        ];
    }

    private function requireTask(string $taskName): \Malsa\TaskOrchestrator\Domain\TaskDefinition
    {
        $task = $this->tasks->find($taskName);

        if ($task === null) {
            throw new NotFoundHttpException(sprintf('Task "%s" is not registered.', $taskName));
        }

        return $task;
    }

    private function resolveCommand(string $commandName): ?object
    {
        if (trim($commandName) === '') {
            return null;
        }

        try {
            $commands = Artisan::all();

            $command = $commands[$commandName] ?? null;

            return is_object($command) ? $command : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

