<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\CurrentTaskRunStore;
use Malsa\TaskOrchestrator\Support\TaskProgressUpdater;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for TaskProgressUpdater.
 *
 * Validates: Requirements 11.4, 11.5, 11.6
 */
class TaskProgressUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private TaskProgressUpdater $updater;
    private CurrentTaskRunStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = app(CurrentTaskRunStore::class);
        $this->updater = app(TaskProgressUpdater::class);
    }

    /**
     * Test: set() updates progress fields on the TaskRunRecord in the database.
     *
     * Validates: Requirement 11.4
     */
    public function test_set_updates_record_fields(): void
    {
        $record = TaskRunRecord::query()->create([
            'id' => 'progress-run-id',
            'task_name' => 'import-users',
            'task_label' => 'Import Users',
            'command' => 'app:import-users',
            'status' => 'running',
            'trigger_type' => 'manual',
        ]);

        $this->store->set('progress-run-id');

        $this->updater->set(25, 100, 'Processing batch 1');

        $record->refresh();

        $this->assertSame(25, $record->progress_current);
        $this->assertSame(100, $record->progress_total);
        $this->assertSame('Processing batch 1', $record->progress_message);
    }

    /**
     * Test: set() updates fields with partial arguments (null total and message).
     *
     * Validates: Requirement 11.4
     */
    public function test_set_updates_with_partial_arguments(): void
    {
        $record = TaskRunRecord::query()->create([
            'id' => 'partial-progress-run-id',
            'task_name' => 'sync-data',
            'task_label' => 'Sync Data',
            'command' => 'app:sync-data',
            'status' => 'running',
            'trigger_type' => 'manual',
        ]);

        $this->store->set('partial-progress-run-id');

        $this->updater->set(10);

        $record->refresh();

        $this->assertSame(10, $record->progress_current);
        $this->assertNull($record->progress_total);
        $this->assertNull($record->progress_message);
    }

    /**
     * Test: set() does nothing when no current task run is stored.
     *
     * Validates: Requirement 11.5
     */
    public function test_set_does_nothing_without_current_run(): void
    {
        $record = TaskRunRecord::query()->create([
            'id' => 'orphan-run-id',
            'task_name' => 'export-data',
            'task_label' => 'Export Data',
            'command' => 'app:export-data',
            'status' => 'running',
            'trigger_type' => 'manual',
            'progress_current' => null,
            'progress_total' => null,
            'progress_message' => null,
        ]);

        // Do NOT set a current run in the store
        $this->updater->set(50, 200, 'Should not persist');

        $record->refresh();

        $this->assertNull($record->progress_current);
        $this->assertNull($record->progress_total);
        $this->assertNull($record->progress_message);
    }

    /**
     * Test: clear() resets progress fields to null in the database.
     *
     * Validates: Requirement 11.6
     */
    public function test_clear_resets_fields_to_null(): void
    {
        $record = TaskRunRecord::query()->create([
            'id' => 'clear-run-id',
            'task_name' => 'generate-report',
            'task_label' => 'Generate Report',
            'command' => 'app:generate-report',
            'status' => 'running',
            'trigger_type' => 'manual',
            'progress_current' => 75,
            'progress_total' => 100,
            'progress_message' => 'Almost done',
        ]);

        $this->store->set('clear-run-id');

        $this->updater->clear();

        $record->refresh();

        $this->assertNull($record->progress_current);
        $this->assertNull($record->progress_total);
        $this->assertNull($record->progress_message);
    }
}
