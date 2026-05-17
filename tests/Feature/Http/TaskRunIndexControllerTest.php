<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for TaskRunIndexController.
 *
 * Validates: Requirement 9.5
 */
class TaskRunIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('task-orchestrator.authorization.enabled', false);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('task-orchestrator.middleware', ['web']);
    }

    /**
     * Test: GET returns list of task runs.
     *
     * Validates: Requirement 9.5
     */
    public function test_get_returns_list_of_task_runs(): void
    {
        TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'import-users',
            'task_label' => 'Import Users',
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'manual',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(5),
        ]);

        TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'sync-data',
            'task_label' => 'Sync Data',
            'command' => 'app:sync-data',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'scheduled',
            'started_at' => now()->subMinutes(3),
            'finished_at' => now()->subMinutes(1),
        ]);

        $response = $this->get(route('task-orchestrator.runs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('task-orchestrator::runs.index');
        $response->assertViewHas('runs');

        $runs = $response->viewData('runs');
        $this->assertSame(2, $runs->total());
    }
}
