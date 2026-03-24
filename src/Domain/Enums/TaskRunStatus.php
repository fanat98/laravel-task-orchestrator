<?php
declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain\Enums;

enum TaskRunStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
