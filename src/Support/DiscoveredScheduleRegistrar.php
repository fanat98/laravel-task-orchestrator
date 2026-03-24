<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Console\Scheduling\Schedule;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class DiscoveredScheduleRegistrar
{
    public function register(Schedule $schedule, TaskOrchestratorManager $tasks): void
    {
        foreach ($tasks->all() as $task) {
            $this->registerTaskSchedule($schedule, $task);
        }
    }

    private function registerTaskSchedule(Schedule $schedule, TaskDefinition $task): void
    {
        if (! $task->schedule || empty($task->schedule['expression'])) {
            return;
        }

        $event = $schedule->command(
            sprintf('task-orchestrator:run-scheduled-task %s', escapeshellarg($task->name))
        );

        $event->cron($task->schedule['expression']);

        if (! $task->allowConcurrentRuns) {
            $event->withoutOverlapping();
        }
    }
}
