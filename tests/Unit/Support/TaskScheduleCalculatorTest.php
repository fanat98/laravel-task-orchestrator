<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskScheduleCalculatorTest extends TestCase
{
    private TaskScheduleCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TaskScheduleCalculator();
    }

    public function test_valid_cron_expression_returns_future_carbon_immutable(): void
    {
        $result = $this->calculator->nextRun(['expression' => '*/5 * * * *']);

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertTrue($result->greaterThan(CarbonImmutable::now()));
    }

    public function test_null_schedule_returns_null(): void
    {
        $result = $this->calculator->nextRun(null);

        $this->assertNull($result);
    }

    public function test_empty_expression_returns_null(): void
    {
        $result = $this->calculator->nextRun(['expression' => '']);

        $this->assertNull($result);
    }

    public function test_whitespace_only_expression_returns_null(): void
    {
        $result = $this->calculator->nextRun(['expression' => '   ']);

        $this->assertNull($result);
    }

    public function test_invalid_expression_returns_null(): void
    {
        $result = $this->calculator->nextRun(['expression' => 'not-a-cron']);

        $this->assertNull($result);
    }

    public function test_schedule_without_expression_key_returns_null(): void
    {
        $result = $this->calculator->nextRun(['other_key' => '*/5 * * * *']);

        $this->assertNull($result);
    }
}
