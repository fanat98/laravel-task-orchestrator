<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\ValueObjects\Progress;
use Malsa\TaskOrchestrator\Tests\TestCase;

class ProgressTest extends TestCase
{
    public function test_valid_construction(): void
    {
        $progress = new Progress(current: 5, total: 10, message: 'Processing');

        $this->assertSame(5, $progress->current);
        $this->assertSame(10, $progress->total);
        $this->assertSame('Processing', $progress->message);
    }

    public function test_valid_construction_with_null_total(): void
    {
        $progress = new Progress(current: 3);

        $this->assertSame(3, $progress->current);
        $this->assertNull($progress->total);
        $this->assertNull($progress->message);
    }

    public function test_negative_current_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Progress current value cannot be negative.');

        new Progress(current: -1, total: 10);
    }

    public function test_negative_total_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Progress total value cannot be negative.');

        new Progress(current: 0, total: -5);
    }

    public function test_current_greater_than_total_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Progress current value cannot be greater than total.');

        new Progress(current: 11, total: 10);
    }

    public function test_is_complete_returns_true_when_current_equals_total(): void
    {
        $progress = new Progress(current: 10, total: 10);

        $this->assertTrue($progress->isComplete());
    }

    public function test_is_complete_returns_false_when_current_less_than_total(): void
    {
        $progress = new Progress(current: 5, total: 10);

        $this->assertFalse($progress->isComplete());
    }

    public function test_is_complete_returns_false_when_total_is_null(): void
    {
        $progress = new Progress(current: 5);

        $this->assertFalse($progress->isComplete());
    }

    public function test_percentage_returns_null_when_total_is_null(): void
    {
        $progress = new Progress(current: 5);

        $this->assertNull($progress->percentage());
    }

    public function test_percentage_returns_null_when_total_is_zero(): void
    {
        $progress = new Progress(current: 0, total: 0);

        $this->assertNull($progress->percentage());
    }

    public function test_percentage_returns_correct_value(): void
    {
        $progress = new Progress(current: 5, total: 10);

        $this->assertSame(50.0, $progress->percentage());
    }

    public function test_percentage_returns_100_when_complete(): void
    {
        $progress = new Progress(current: 10, total: 10);

        $this->assertSame(100.0, $progress->percentage());
    }

    public function test_percentage_returns_zero_when_current_is_zero(): void
    {
        $progress = new Progress(current: 0, total: 10);

        $this->assertSame(0.0, $progress->percentage());
    }
}
