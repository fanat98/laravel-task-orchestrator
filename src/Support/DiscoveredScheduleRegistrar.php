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

        // In-process callback instead of Schedule::command(): no shell is required.
        $event = $schedule->call(new ScheduledArtisanCallback(
            'task-orchestrator:run-scheduled-task',
            ['task' => $task->name],
        ));

        // Stable, unique name: required for withoutOverlapping() and used as mutex key.
        $event->name(sprintf('task-orchestrator:run-scheduled-task %s', $task->name));
        $event->cron($task->schedule['expression']);

        if (! $task->allowConcurrentRuns) {
            $event->withoutOverlapping();
        }
    }
}
