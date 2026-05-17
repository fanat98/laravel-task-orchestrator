<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Domain\ValueObjects\Progress;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskRunTest extends TestCase
{
    public function test_valid_construction_with_required_parameters(): void
    {
        $run = new TaskRun(
            id: 'run-123',
            taskName: 'import-users',
            status: TaskRunStatus::Queued,
        );

        $this->assertInstanceOf(TaskRun::class, $run);
        $this->assertSame('run-123', $run->id);
        $this->assertSame('import-users', $run->taskName);
        $this->assertSame(TaskRunStatus::Queued, $run->status);
        $this->assertNull($run->progress);
        $this->assertNull($run->startedAt);
        $this->assertNull($run->finishedAt);
        $this->assertNull($run->failureMessage);
    }

    public function test_valid_construction_with_all_parameters(): void
    {
        $progress = new Progress(current: 5, total: 10, message: 'Processing');
        $startedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $finishedAt = new DateTimeImmutable('2024-01-01 10:05:00');

        $run = new TaskRun(
            id: 'run-456',
            taskName: 'export-data',
            status: TaskRunStatus::Succeeded,
            progress: $progress,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            failureMessage: null,
        );

        $this->assertSame('run-456', $run->id);
        $this->assertSame('export-data', $run->taskName);
        $this->assertSame(TaskRunStatus::Succeeded, $run->status);
        $this->assertSame($progress, $run->progress);
        $this->assertSame($startedAt, $run->startedAt);
        $this->assertSame($finishedAt, $run->finishedAt);
        $this->assertNull($run->failureMessage);
    }

    public function test_empty_id_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task run id cannot be empty.');

        new TaskRun(
            id: '',
            taskName: 'import-users',
            status: TaskRunStatus::Queued,
        );
    }

    public function test_empty_task_name_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task name cannot be empty.');

        new TaskRun(
            id: 'run-123',
            taskName: '',
            status: TaskRunStatus::Queued,
        );
    }

    public function test_all_properties_are_accessible(): void
    {
        $progress = new Progress(current: 3, total: 10);
        $startedAt = new DateTimeImmutable('2024-06-15 08:00:00');
        $finishedAt = new DateTimeImmutable('2024-06-15 08:10:00');

        $run = new TaskRun(
            id: 'run-789',
            taskName: 'sync-orders',
            status: TaskRunStatus::Failed,
            progress: $progress,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            failureMessage: 'Connection timeout',
        );

        $this->assertSame('run-789', $run->id);
        $this->assertSame('sync-orders', $run->taskName);
        $this->assertSame(TaskRunStatus::Failed, $run->status);
        $this->assertSame($progress, $run->progress);
        $this->assertSame($startedAt, $run->startedAt);
        $this->assertSame($finishedAt, $run->finishedAt);
        $this->assertSame('Connection timeout', $run->failureMessage);
    }
}
