<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Console;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for RecoverStaleTaskRunsCommand.
 *
 * Validates: Requirement 12.2
 */
class RecoverStaleTaskRunsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: marks stale running tasks as failed.
     *
     * Validates: Requirement 12.2
     */
    public function test_marks_stale_running_tasks_as_failed(): void
    {
        // Create a run that started 20 minutes ago (stale with default 10 min timeout)
        $staleRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'stale-task',
            'task_label' => 'Stale Task',
            'command' => 'app:stale-task',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'scheduled',
            'started_at' => Carbon::now()->subMinutes(20),
        ]);

        $this->artisan('task-orchestrator:recover-stale-runs')
            ->assertSuccessful();

        $staleRun->refresh();

        $this->assertSame(TaskRunStatus::Failed->value, $staleRun->status);
        $this->assertNotNull($staleRun->failure_message);
        $this->assertStringContainsString('automatically marked as failed', $staleRun->failure_message);
        $this->assertNotNull($staleRun->finished_at);
    }

    /**
     * Test: does not mark recent running tasks as failed.
     *
     * Validates: Requirement 12.2
     */
    public function test_does_not_mark_recent_running_tasks_as_failed(): void
    {
        // Create a run that started 2 minutes ago (not stale with default 10 min timeout)
        $recentRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'recent-task',
            'task_label' => 'Recent Task',
            'command' => 'app:recent-task',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'scheduled',
            'started_at' => Carbon::now()->subMinutes(2),
        ]);

        $this->artisan('task-orchestrator:recover-stale-runs')
            ->assertSuccessful();

        $recentRun->refresh();

        $this->assertSame(TaskRunStatus::Running->value, $recentRun->status);
        $this->assertNull($recentRun->failure_message);
    }

    /**
     * Test: respects --minutes override for all tasks.
     *
     * Validates: Requirement 12.2
     */
    public function test_respects_minutes_override(): void
    {
        // Create a run that started 6 minutes ago
        $run = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'override-task',
            'task_label' => 'Override Task',
            'command' => 'app:override-task',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
            'started_at' => Carbon::now()->subMinutes(6),
        ]);

        // With --minutes=5, the 6-minute-old run should be stale
        $this->artisan('task-orchestrator:recover-stale-runs', ['--minutes' => 5])
            ->assertSuccessful();

        $run->refresh();

        $this->assertSame(TaskRunStatus::Failed->value, $run->status);
    }

    /**
     * Test: outputs "No stale runs found" when nothing to recover.
     *
     * Validates: Requirement 12.2
     */
    public function test_outputs_no_stale_runs_message_when_nothing_to_recover(): void
    {
        $this->artisan('task-orchestrator:recover-stale-runs')
            ->expectsOutput('No stale runs found.')
            ->assertSuccessful();
    }
}
