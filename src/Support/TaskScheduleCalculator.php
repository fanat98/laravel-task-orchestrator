<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Throwable;

final class TaskScheduleCalculator
{
    public function nextRun(?array $schedule): ?CarbonImmutable
    {
        $expression = $schedule['expression'] ?? null;

        if (! is_string($expression) || trim($expression) === '') {
            return null;
        }

        try {
            $cron = new CronExpression($expression);

            return CarbonImmutable::instance($cron->getNextRunDate());
        } catch (Throwable) {
            return null;
        }
    }
}
