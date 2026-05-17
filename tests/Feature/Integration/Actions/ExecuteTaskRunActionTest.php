<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Tests\TestCase;
use RuntimeException;

/**
 * Integration tests for ExecuteTaskRunAction.
 *
 * Validates: Requirements 6.11, 6.12, 6.13, 6.14, 6.15
 */
class ExecuteTaskRunActionTest extends TestCase
{
    use RefreshDatabase;

    private ExecuteTaskRunAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(ExecuteTaskRunAction::class);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('task-orchestrator.stale_run_default_minutes', 10);
    }

    /**
     * Test: transitions status from queued → running → succeeded for a successful command.
     *
     * Validates: Requirement 6.11
     */
    public function test_transitions_queued_to_running_to_succeeded(): void
    {
        // Register a test command that exits with code 0
        Artisan::command('test:success-command', function (): void {
            // Command does nothing and exits successfully
        });

        $run = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'test-success',
            'task_label' => 'Test Success',
            'command' => 'test:success-command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $this->action->execute($run->id);

        $run->refresh();

        $this->assertSame(TaskRunStatus::Succeeded->value, $run->status);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);
        $this->assertNull($run->failure_message);
    }

    /**
     * Test: transitions status to failed when command returns non-zero exit code.
     *
     * Validates: Requirement 6.12
     */
    public function test_transitions_to_failed_on_non_zero_exit_code(): void
    {
        // Register a test command that exits with non-zero code
        Artisan::command('test:failing-command', function (): int {
            return 1;
        });

        $run = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'test-failing',
            'task_label' => 'Test Failing',
            'command' => 'test:failing-command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $this->action->execute($run->id);

        $run->refresh();

        $this->assertSame(TaskRunStatus::Failed->value, $run->status);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->failure_message);
        $this->assertStringContainsString('test:failing-command', $run->failure_message);
        $this->assertStringContainsString('exit code', $run->failure_message);
    }

    /**
     * Test: transitions status to failed and re-throws when command throws exception.
     *
     * Validates: Requirement 6.13
     */
    public function test_transitions_to_failed_and_rethrows_on_exception(): void
    {
        // Register a test command that throws an exception
        Artisan::command('test:exception-command', function (): void {
            throw new RuntimeException('Something went wrong');
        });

        $run = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'test-exception',
            'task_label' => 'Test Exception',
            'command' => 'test:exception-command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Something went wrong');

        try {
            $this->action->execute($run->id);
        } finally {
            $run->refresh();

            $this->assertSame(TaskRunStatus::Failed->value, $run->status);
            $this->assertNotNull($run->started_at);
            $this->assertNotNull($run->finished_at);
            $this->assertSame('Something went wrong', $run->failure_message);
        }
    }

    /**
     * Test: skips execution for already-terminal runs (succeeded, failed, cancelled).
     *
     * Validates: Requirement 6.14
     */
    public function test_skips_terminal_runs(): void
    {
        $terminalStatuses = [
            TaskRunStatus::Succeeded->value,
            TaskRunStatus::Failed->value,
            TaskRunStatus::Cancelled->value,
        ];

        foreach ($terminalStatuses as $status) {
            $run = TaskRunRecord::query()->create([
                'id' => Str::uuid()->toString(),
                'task_name' => 'test-terminal',
                'task_label' => 'Test Terminal',
                'command' => 'test:should-not-run',
                'status' => $status,
                'trigger_type' => 'manual',
            ]);

            $this->action->execute($run->id);

            $run->refresh();

            // Status should remain unchanged
            $this->assertSame($status, $run->status, "Expected status to remain '{$status}' for terminal run");
        }
    }

    /**
     * Test: triggers downstream tasks on success.
     *
     * Since StartDownstreamTasksAction is final and cannot be mocked,
     * we verify the downstream triggering by registering a dependent task
     * and checking that a new run is created for it after the upstream succeeds.
     *
     * Validates: Requirement 6.15
     */
    public function test_triggers_downstream_on_success(): void
    {
        Queue::fake();

        // Register a test command that exits with code 0
        Artisan::command('test:downstream-trigger', function (): void {
            // Command does nothing and exits successfully
        });

        // Register the upstream task and a downstream dependent
        $manager = app(\Malsa\TaskOrchestrator\Support\TaskOrchestratorManager::class);
        $manager->register(
            \Malsa\TaskOrchestrator\Domain\TaskDefinition::make('test-downstream')
                ->label('Test Downstream')
                ->command('test:downstream-trigger')
        );
        $manager->register(
            \Malsa\TaskOrchestrator\Domain\TaskDefinition::make('dependent-task')
                ->label('Dependent Task')
                ->command('app:dependent')
                ->dependsOn(['test-downstream'])
        );

        $pipelineId = Str::uuid()->toString();

        $run = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'test-downstream',
            'task_label' => 'Test Downstream',
            'command' => 'test:downstream-trigger',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        $action = app(ExecuteTaskRunAction::class);
        $action->execute($run->id);

        $run->refresh();
        $this->assertSame(TaskRunStatus::Succeeded->value, $run->status);

        // Verify that a downstream task run was created for the dependent task
        $downstreamRun = TaskRunRecord::query()
            ->where('task_name', 'dependent-task')
            ->where('pipeline_id', $pipelineId)
            ->first();

        $this->assertNotNull(
            $downstreamRun,
            'Expected a downstream task run to be created for the dependent task'
        );
        $this->assertSame(TaskRunStatus::Queued->value, $downstreamRun->status);
        $this->assertSame('pipeline', $downstreamRun->trigger_type);
    }
}
