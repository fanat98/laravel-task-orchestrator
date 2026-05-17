<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\TaskDependencyValidator;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for TaskDependencyValidator.
 *
 * **Validates: Requirements 10.5**
 */
class TaskDependencyValidatorPropertyTest extends TestCase
{
    use BlackBox;

    private TaskDependencyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new TaskDependencyValidator();
    }

    /**
     * Property: for all valid DAGs (no cycles), validation passes.
     *
     * We generate a linear chain of tasks (A → B → C → ...) which is always a valid DAG.
     * Each task only depends on the previous one, guaranteeing no cycles.
     *
     * **Validates: Requirements 10.5**
     *
     * @group property
     */
    public function test_valid_dags_always_pass_validation(): void
    {
        self::forAll(
            Set\Integers::between(2, 10),
        )
            ->take(200)
            ->then(function (int $chainLength) {
                $tasks = [];

                // Build a linear chain: task-0 has no deps, task-1 depends on task-0, etc.
                for ($i = 0; $i < $chainLength; $i++) {
                    $definition = TaskDefinition::make("task-{$i}")
                        ->label("Task {$i}")
                        ->command("app:task-{$i}");

                    if ($i > 0) {
                        $definition = $definition->dependsOn(["task-" . ($i - 1)]);
                    }

                    $tasks[] = $definition;
                }

                // Should not throw — this is always a valid DAG
                $this->validator->validate($tasks);

                // If we reach here, validation passed
                $this->assertTrue(true);
            });
    }
}
