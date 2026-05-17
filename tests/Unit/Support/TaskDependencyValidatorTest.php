<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\TaskDependencyValidator;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskDependencyValidatorTest extends TestCase
{
    private TaskDependencyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new TaskDependencyValidator();
    }

    public function test_valid_dag_passes_validation(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b']),
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-c']),
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c'),
        ];

        $this->validator->validate($tasks);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_unknown_dependency_throws_invalid_argument_exception(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['non-existent-task']),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "task-a" depends on unknown task "non-existent-task".');

        $this->validator->validate($tasks);
    }

    public function test_self_dependency_throws_invalid_argument_exception(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-a']),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "task-a" cannot depend on itself.');

        $this->validator->validate($tasks);
    }

    public function test_simple_cycle_throws_with_path(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b']),
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-a']),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular task dependency detected:');

        $this->validator->validate($tasks);
    }

    public function test_complex_cycle_throws_with_path(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b']),
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-c']),
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
                ->dependsOn(['task-a']),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular task dependency detected:');

        $this->validator->validate($tasks);
    }

    public function test_tasks_with_no_dependencies_pass_validation(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a'),
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b'),
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c'),
        ];

        $this->validator->validate($tasks);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_diamond_dependency_passes_validation(): void
    {
        $tasks = [
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b', 'task-c']),
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-d']),
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
                ->dependsOn(['task-d']),
            TaskDefinition::make('task-d')
                ->label('Task D')
                ->command('app:task-d'),
        ];

        $this->validator->validate($tasks);

        $this->assertTrue(true); // No exception thrown
    }
}
