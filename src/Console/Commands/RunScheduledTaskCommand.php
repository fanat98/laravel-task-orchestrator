<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Illuminate\Console\Command;
use Malsa\TaskOrchestrator\Actions\StartTaskChainAction;

final class RunScheduledTaskCommand extends Command
{
    protected $signature = 'task-orchestrator:run-scheduled-task {task}';

    protected $description = 'Starts a scheduled task through the Task Orchestrator';

    public function __construct(
        private readonly StartTaskChainAction $startTaskChain,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $taskName = (string) $this->argument('task');

        $this->startTaskChain->execute($taskName, 'scheduled');

        $this->info(sprintf(
            'Scheduled task [%s] was dispatched through the orchestrator.',
            $taskName
        ));

        return self::SUCCESS;
    }
}
