<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\ValueObjects\Progress;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for Progress value object invariants.
 *
 * **Validates: Requirements 10.1, 10.2**
 */
class ProgressInvariantPropertyTest extends TestCase
{
    use BlackBox;

    /**
     * Property: for all valid (current, total) pairs where current ≤ total,
     * construction succeeds and percentage() ≤ 100.
     *
     * **Validates: Requirements 10.1**
     *
     * @group property
     */
    public function test_valid_pairs_succeed_and_percentage_lte_100(): void
    {
        self::forAll(
            Set\Integers::between(0, 10000),
            Set\Integers::between(0, 10000),
        )
            ->filter(fn (int $current, int $total) => $current <= $total)
            ->take(200)
            ->then(function (int $current, int $total) {
                $progress = new Progress($current, $total);

                $this->assertSame($current, $progress->current);
                $this->assertSame($total, $progress->total);

                if ($total > 0) {
                    $this->assertNotNull($progress->percentage());
                    $this->assertLessThanOrEqual(100.0, $progress->percentage());
                    $this->assertGreaterThanOrEqual(0.0, $progress->percentage());
                }
            });
    }

    /**
     * Property: for all negative current values, construction throws.
     *
     * **Validates: Requirements 10.2**
     *
     * @group property
     */
    public function test_negative_current_always_throws(): void
    {
        self::forAll(
            Set\Integers::between(-10000, -1),
            Set\Integers::between(0, 10000),
        )
            ->take(200)
            ->then(function (int $negativeCurrent, int $total) {
                $this->expectException(\InvalidArgumentException::class);

                new Progress($negativeCurrent, $total);
            });
    }
}
