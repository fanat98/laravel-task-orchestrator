<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Carbon\CarbonImmutable;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for TaskScheduleCalculator.
 *
 * **Validates: Requirements 10.7**
 */
class TaskScheduleCalculatorPropertyTest extends TestCase
{
    use BlackBox;

    private TaskScheduleCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new TaskScheduleCalculator();
    }

    /**
     * Property: for all valid cron expressions, nextRun() returns a non-null future date.
     *
     * **Validates: Requirements 10.7**
     *
     * @group property
     */
    public function test_valid_cron_expressions_produce_future_dates(): void
    {
        self::forAll(
            Set::of(
                '* * * * *',
                '0 * * * *',
                '*/5 * * * *',
                '0 0 * * *',
                '0 0 * * 0',
                '30 2 * * *',
                '0 6 * * 1-5',
                '0 0 1 * *',
                '0 0 1 1 *',
                '*/15 * * * *',
                '0 */2 * * *',
                '0 0 * * 1,3,5',
                '5 4 * * *',
                '0 22 * * 1-5',
                '0 9 1-7 * 1',
            ),
        )
            ->take(200)
            ->then(function (string $expression) {
                $now = CarbonImmutable::now();

                $result = $this->calculator->nextRun(['expression' => $expression]);

                $this->assertNotNull($result, "Valid cron expression '{$expression}' should produce a non-null result.");
                $this->assertInstanceOf(CarbonImmutable::class, $result);
                $this->assertTrue(
                    $result->greaterThan($now),
                    "Next run for '{$expression}' should be in the future."
                );
            });
    }
}
