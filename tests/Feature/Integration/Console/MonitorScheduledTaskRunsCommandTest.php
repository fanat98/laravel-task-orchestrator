<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

class MonitorScheduledTaskRunsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_successful_when_all_scheduled_tasks_are_recent(): void
    {
        Carbon::setTestNow('2026-06-05 03:00:00');

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('watch-end-date')
                ->label('Watch End Date')
                ->command('watch:end-date')
                ->schedule(['expression' => '0 2 * * *', 'human' => 'Daily at 02:00'])
        );

        TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'watch-end-date',
            'task_label' => 'Watch End Date',
            'command' => 'watch:end-date',
            'status' => 'success',
            'trigger_type' => 'scheduled',
            'started_at' => Carbon::parse('2026-06-05 02:00:09'),
            'finished_at' => Carbon::parse('2026-06-05 02:00:30'),
        ]);

        $this->artisan('task-orchestrator:monitor-scheduled-runs')
            ->expectsOutput('Scheduled monitoring healthy: checked 1 task(s), grace 20 minute(s).')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_command_fails_when_scheduled_task_missed_run_window(): void
    {
        Carbon::setTestNow('2026-06-06 03:00:00');

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('watch-end-date')
                ->label('Watch End Date')
                ->command('watch:end-date')
                ->schedule(['expression' => '0 2 * * *', 'human' => 'Daily at 02:00'])
        );

        TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'watch-end-date',
            'task_label' => 'Watch End Date',
            'command' => 'watch:end-date',
            'status' => 'success',
            'trigger_type' => 'scheduled',
            'started_at' => Carbon::parse('2026-06-05 02:00:09'),
            'finished_at' => Carbon::parse('2026-06-05 02:00:30'),
        ]);

        $this->artisan('task-orchestrator:monitor-scheduled-runs')
            ->expectsOutput('Found 1 missed scheduled task(s).')
            ->assertExitCode(Command::FAILURE);

        Carbon::setTestNow();
    }

    public function test_command_can_be_configured_to_not_fail_on_missed_tasks(): void
    {
        Carbon::setTestNow('2026-06-06 03:00:00');

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('watch-end-date')
                ->label('Watch End Date')
                ->command('watch:end-date')
                ->schedule(['expression' => '0 2 * * *', 'human' => 'Daily at 02:00'])
        );

        $this->artisan('task-orchestrator:monitor-scheduled-runs', ['--fail-on-missed' => 'false'])
            ->expectsOutput('Found 1 missed scheduled task(s).')
            ->assertSuccessful();

        Carbon::setTestNow();
    }
}

