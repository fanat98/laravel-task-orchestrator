<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class ExecutionBlockingGuard
{
    public function __construct(
        private readonly TaskStartBlockingEvaluator $evaluator,
    ) {
    }

    /**
     * @return array{
     *     can_start: bool,
     *     reason: string|null,
     *     active_run_id: string|null
     * }
     */
    public function evaluate(TaskDefinition $definition): array
    {
        $relatedTaskNames = array_values(array_unique(array_merge([$definition->name], $definition->dependsOn)));
        $activeRunsByTask = $this->evaluator->activeRunsByTaskName($relatedTaskNames);
        $latestRunsByTask = $this->evaluator->latestRunsByTaskName($relatedTaskNames);

        $evaluation = $this->evaluator->evaluate($definition, $activeRunsByTask, $latestRunsByTask, false);

        return [
            'can_start' => $evaluation['can_start'],
            'reason' => $evaluation['start_block_reason'],
            'active_run_id' => $evaluation['can_start'] ? null : $evaluation['active_run_id'],
        ];
    }

    public function ensureCanStart(TaskDefinition $definition): void
    {
        $result = $this->evaluate($definition);

        if ($result['can_start']) {
            return;
        }

        if (in_array($result['reason'], ['same_task_running', 'same_task_queued'], true)) {
            throw new \RuntimeException(sprintf(
                'Task "%s" is already queued or running.',
                $definition->name
            ));
        }

        if (in_array($result['reason'], ['dependency_active', 'dependency_not_succeeded'], true)) {
            throw new \RuntimeException(sprintf(
                'Task "%s" is blocked by dependencies.',
                $definition->name
            ));
        }

        throw new \RuntimeException(sprintf(
            'Task "%s" cannot be started.',
            $definition->name
        ));
    }
}

