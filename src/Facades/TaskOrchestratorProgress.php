<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void set(int $current, ?int $total = null, ?string $message = null)
 * @method static void clear()
 */
final class TaskOrchestratorProgress extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'task-orchestrator.progress';
    }
}
