<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\ExecutionBlockingGuard;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for ExecutionBlockingGuard.
 *
 * Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6
 */
class ExecutionBlockingGuardTest extends TestCase
{
    use RefreshDatabase;

    private ExecutionBlockingGuard $guard;
    private TaskOrchestratorManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = app(ExecutionBlockingGuard::class);
        $this->manager = app(TaskOrchestratorManager::class);
    }

    /**
     * Test: evaluate() returns can_start: true when no blocking conditions exist.
     *
     * Validates: Requirement 7.1
     */
    public function test_can_start_true_when_no_blocking_conditions(): void
    {
        $definition = TaskDefinition::make('clean-logs')
            ->label('Clean Logs')
            ->command('app:clean-logs');

        $this->manager->register($definition);

        $result = $this->guard->evaluate($definition);

        $this->assertTrue($result['can_start']);
        $this->assertNull($result['reason']);
        $this->assertNull($result['active_run_id']);
    }

    /**
     * Test: evaluate() returns can_start: false with reason 'same_task_running'
     * when task is running and concurrent runs are disabled.
     *
     * Validates: Requirement 7.2
     */
    public function test_blocked_by_same_task_running(): void
    {
        $definition = TaskDefinition::make('import-data')
            ->label('Import Data')
            ->command('app:import-data')
            ->allowConcurrentRuns(false);

        $this->manager->register($definition);

        TaskRunRecord::query()->create([
            'id' => 'running-run-id',
            'task_name' => 'import-data',
            'task_label' => 'Import Data',
            'command' => 'app:import-data',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->guard->evaluate($definition);

        $this->assertFalse($result['can_start']);
        $this->assertSame('same_task_running', $result['reason']);
        $this->assertSame('running-run-id', $result['active_run_id']);
    }

    /**
     * Test: evaluate() returns can_start: false with reason 'same_task_queued'
     * when task is queued and concurrent runs are disabled.
     *
     * Validates: Requirement 7.3
     */
    public function test_blocked_by_same_task_queued(): void
    {
        $definition = TaskDefinition::make('sync-users')
            ->label('Sync Users')
            ->command('app:sync-users')
            ->allowConcurrentRuns(false);

        $this->manager->register($definition);

        TaskRunRecord::query()->create([
            'id' => 'queued-run-id',
            'task_name' => 'sync-users',
            'task_label' => 'Sync Users',
            'command' => 'app:sync-users',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->guard->evaluate($definition);

        $this->assertFalse($result['can_start']);
        $this->assertSame('same_task_queued', $result['reason']);
        $this->assertSame('queued-run-id', $result['active_run_id']);
    }

    /**
     * Test: evaluate() returns can_start: true when task is running but concurrent runs are enabled.
     *
     * Validates: Requirement 7.4
     */
    public function test_allowed_when_concurrent_runs_enabled(): void
    {
        $definition = TaskDefinition::make('send-emails')
            ->label('Send Emails')
            ->command('app:send-emails')
            ->allowConcurrentRuns(true);

        $this->manager->register($definition);

        TaskRunRecord::query()->create([
            'id' => 'concurrent-run-id',
            'task_name' => 'send-emails',
            'task_label' => 'Send Emails',
            'command' => 'app:send-emails',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->guard->evaluate($definition);

        $this->assertTrue($result['can_start']);
        $this->assertNull($result['reason']);
        $this->assertNull($result['active_run_id']);
    }

    /**
     * Test: evaluate() returns can_start: false with reason 'dependency_active'
     * when a dependency is currently running.
     *
     * Validates: Requirement 7.5
     */
    public function test_blocked_by_dependency_active(): void
    {
        $dependencyDefinition = TaskDefinition::make('fetch-data')
            ->label('Fetch Data')
            ->command('app:fetch-data');

        $definition = TaskDefinition::make('process-data')
            ->label('Process Data')
            ->command('app:process-data')
            ->dependsOn(['fetch-data']);

        $this->manager->register($dependencyDefinition);
        $this->manager->register($definition);

        // Dependency is currently running
        TaskRunRecord::query()->create([
            'id' => 'dep-running-id',
            'task_name' => 'fetch-data',
            'task_label' => 'Fetch Data',
            'command' => 'app:fetch-data',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->guard->evaluate($definition);

        $this->assertFalse($result['can_start']);
        $this->assertSame('dependency_active', $result['reason']);
    }

    /**
     * Test: evaluate() returns can_start: false with reason 'dependency_not_succeeded'
     * when dependency's latest run is not succeeded.
     *
     * Validates: Requirement 7.6
     */
    public function test_blocked_by_dependency_not_succeeded(): void
    {
        $dependencyDefinition = TaskDefinition::make('validate-data')
            ->label('Validate Data')
            ->command('app:validate-data');

        $definition = TaskDefinition::make('transform-data')
            ->label('Transform Data')
            ->command('app:transform-data')
            ->dependsOn(['validate-data']);

        $this->manager->register($dependencyDefinition);
        $this->manager->register($definition);

        // Dependency's latest run failed (not active, but not succeeded)
        TaskRunRecord::query()->create([
            'id' => 'dep-failed-id',
            'task_name' => 'validate-data',
            'task_label' => 'Validate Data',
            'command' => 'app:validate-data',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'manual',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(5),
        ]);

        $result = $this->guard->evaluate($definition);

        $this->assertFalse($result['can_start']);
        $this->assertSame('dependency_not_succeeded', $result['reason']);
    }
}
