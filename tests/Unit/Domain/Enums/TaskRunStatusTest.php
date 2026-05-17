<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Domain\Enums;

use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use PHPUnit\Framework\TestCase;

class TaskRunStatusTest extends TestCase
{
    public function test_all_expected_cases_exist(): void
    {
        $cases = TaskRunStatus::cases();

        $this->assertCount(6, $cases);
    }

    public function test_pending_case_has_correct_value(): void
    {
        $this->assertSame('pending', TaskRunStatus::Pending->value);
    }

    public function test_queued_case_has_correct_value(): void
    {
        $this->assertSame('queued', TaskRunStatus::Queued->value);
    }

    public function test_running_case_has_correct_value(): void
    {
        $this->assertSame('running', TaskRunStatus::Running->value);
    }

    public function test_succeeded_case_has_correct_value(): void
    {
        $this->assertSame('succeeded', TaskRunStatus::Succeeded->value);
    }

    public function test_failed_case_has_correct_value(): void
    {
        $this->assertSame('failed', TaskRunStatus::Failed->value);
    }

    public function test_cancelled_case_has_correct_value(): void
    {
        $this->assertSame('cancelled', TaskRunStatus::Cancelled->value);
    }

    public function test_enum_is_string_backed(): void
    {
        foreach (TaskRunStatus::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function test_cases_can_be_created_from_string_values(): void
    {
        $this->assertSame(TaskRunStatus::Pending, TaskRunStatus::from('pending'));
        $this->assertSame(TaskRunStatus::Queued, TaskRunStatus::from('queued'));
        $this->assertSame(TaskRunStatus::Running, TaskRunStatus::from('running'));
        $this->assertSame(TaskRunStatus::Succeeded, TaskRunStatus::from('succeeded'));
        $this->assertSame(TaskRunStatus::Failed, TaskRunStatus::from('failed'));
        $this->assertSame(TaskRunStatus::Cancelled, TaskRunStatus::from('cancelled'));
    }
}
