<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Contracts;

use Malsa\TaskOrchestrator\Support\TaskContext;

interface TaskHandler
{
    public function handle(TaskContext $context): void;
}
