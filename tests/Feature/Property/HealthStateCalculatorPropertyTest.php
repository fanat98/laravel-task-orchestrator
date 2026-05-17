<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Support\HealthStateCalculator;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for HealthStateCalculator.
 *
 * **Validates: Requirements 10.6**
 */
class HealthStateCalculatorPropertyTest extends TestCase
{
    use BlackBox;

    private HealthStateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new HealthStateCalculator();
    }

    /**
     * Property: for all valid input combinations, overallStatus() returns one of {healthy, warning, critical}.
     *
     * **Validates: Requirements 10.6**
     *
     * @group property
     */
    public function test_all_input_combinations_produce_valid_status(): void
    {
        self::forAll(
            Set::of('healthy', 'busy', 'stuck'),
            Set::of('running', 'down'),
            Set::of('running', 'down'),
            Set\Integers::between(0, 1000),
        )
            ->take(200)
            ->then(function (string $queueStatus, string $schedulerStatus, string $queueWorkerStatus, int $pendingJobs) {
                $result = $this->calculator->overallStatus(
                    $queueStatus,
                    $schedulerStatus,
                    $queueWorkerStatus,
                    $pendingJobs,
                );

                $this->assertContains($result, ['healthy', 'warning', 'critical']);
            });
    }
}
