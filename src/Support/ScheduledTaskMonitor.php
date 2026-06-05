<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Throwable;

final readonly class ScheduledTaskMonitor
{
    public function __construct(
        private TaskOrchestratorManager $tasks,
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     checked_tasks: int,
     *     missed_count: int,
     *     grace_minutes: int,
     *     message: string,
     *     missed_tasks: array<int, array{
     *         task_name: string,
     *         task_label: string,
     *         group: string|null,
     *         schedule_expression: string,
     *         last_due_at: string,
     *         last_scheduled_run_at: string|null,
     *         minutes_overdue: int
     *     }>
     * }
     */
    public function inspect(?int $graceMinutesOverride = null, ?CarbonImmutable $now = null): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $effectiveNow = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $graceMinutes = $this->resolveGraceMinutes($graceMinutesOverride);

        $scheduledTasks = $this->tasks->all()
            ->filter(static fn (TaskDefinition $task): bool =>
                is_array($task->schedule)
                && is_string($task->schedule['expression'] ?? null)
                && trim((string) $task->schedule['expression']) !== ''
            )
            ->values();

        $latestRunsByTask = $this->latestScheduledRunsByTaskName($scheduledTasks->pluck('name')->all());
        $missedTasks = [];

        foreach ($scheduledTasks as $task) {
            $expression = trim((string) $task->schedule['expression']);

            try {
                $cron = new CronExpression($expression);
                $lastDueAt = CarbonImmutable::instance(
                    $cron->getPreviousRunDate($effectiveNow->toDateTimeString(), 0, true, $timezone)
                )->setTimezone($timezone);
            } catch (Throwable) {
                continue;
            }

            if ($effectiveNow->lessThanOrEqualTo($lastDueAt->addMinutes($graceMinutes))) {
                continue;
            }

            $latestRunAt = $latestRunsByTask[$task->name] ?? null;

            if ($latestRunAt !== null && $latestRunAt->greaterThanOrEqualTo($lastDueAt)) {
                continue;
            }

            $missedTasks[] = [
                'task_name' => $task->name,
                'task_label' => $task->label,
                'group' => $task->group,
                'schedule_expression' => $expression,
                'last_due_at' => $lastDueAt->toIso8601String(),
                'last_scheduled_run_at' => $latestRunAt?->toIso8601String(),
                'minutes_overdue' => max(0, (int) $lastDueAt->diffInMinutes($effectiveNow)),
            ];
        }

        $missedCount = count($missedTasks);

        return [
            'status' => $missedCount > 0 ? 'critical' : 'healthy',
            'checked_tasks' => $scheduledTasks->count(),
            'missed_count' => $missedCount,
            'grace_minutes' => $graceMinutes,
            'message' => $missedCount > 0
                ? sprintf('Detected %d missed scheduled task(s).', $missedCount)
                : 'All scheduled tasks are within the expected runtime window.',
            'missed_tasks' => $missedTasks,
        ];
    }

    /**
     * @param array<int, string> $taskNames
     * @return array<string, CarbonImmutable>
     */
    private function latestScheduledRunsByTaskName(array $taskNames): array
    {
        if ($taskNames === []) {
            return [];
        }

        $raw = TaskRunRecord::query()
            ->whereIn('task_name', $taskNames)
            ->where('trigger_type', 'scheduled')
            ->selectRaw('task_name, MAX(COALESCE(started_at, created_at)) as latest_run_at')
            ->groupBy('task_name')
            ->pluck('latest_run_at', 'task_name');

        $result = [];

        foreach ($raw as $taskName => $latestRunAt) {
            if (! is_string($latestRunAt) || trim($latestRunAt) === '') {
                continue;
            }

            try {
                $result[(string) $taskName] = CarbonImmutable::parse($latestRunAt);
            } catch (Throwable) {
                continue;
            }
        }

        return $result;
    }

    private function resolveGraceMinutes(?int $graceMinutesOverride): int
    {
        if ($graceMinutesOverride !== null) {
            return max($graceMinutesOverride, 0);
        }

        return max((int) config('task-orchestrator.scheduled_monitoring.grace_minutes', 20), 0);
    }
}

