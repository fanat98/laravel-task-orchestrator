<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;

final class ExecuteTaskRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $taskRunId,
    ) {
    }

    public function handle(ExecuteTaskRunAction $executeTaskRun): void
    {
        $executeTaskRun->execute($this->taskRunId);
    }
}
