<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Registry;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Registry\InMemoryTaskRegistry;
use Malsa\TaskOrchestrator\Tests\TestCase;

class InMemoryTaskRegistryTest extends TestCase
{
    private InMemoryTaskRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new InMemoryTaskRegistry();
    }

    public function test_register_valid_task_and_find_by_name_returns_it(): void
    {
        $task = TaskDefinition::make('import-users')
            ->label('Import Users')
            ->command('app:import-users');

        $this->registry->register($task);

        $found = $this->registry->findByName('import-users');

        $this->assertNotNull($found);
        $this->assertSame('import-users', $found->name);
        $this->assertSame('Import Users', $found->label);
        $this->assertSame('app:import-users', $found->command);
    }

    public function test_register_multiple_tasks_and_all_returns_all(): void
    {
        $taskA = TaskDefinition::make('task-a')
            ->label('Task A')
            ->command('app:task-a');

        $taskB = TaskDefinition::make('task-b')
            ->label('Task B')
            ->command('app:task-b');

        $taskC = TaskDefinition::make('task-c')
            ->label('Task C')
            ->command('app:task-c');

        $this->registry->register($taskA);
        $this->registry->register($taskB);
        $this->registry->register($taskC);

        $all = $this->registry->all();

        $this->assertCount(3, $all);
        $this->assertSame('task-a', $all[0]->name);
        $this->assertSame('task-b', $all[1]->name);
        $this->assertSame('task-c', $all[2]->name);
    }

    public function test_duplicate_name_throws_invalid_argument_exception(): void
    {
        $task = TaskDefinition::make('import-users')
            ->label('Import Users')
            ->command('app:import-users');

        $this->registry->register($task);

        $duplicate = TaskDefinition::make('import-users')
            ->label('Import Users Again')
            ->command('app:import-users-v2');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A task with the name "import-users" is already registered.');

        $this->registry->register($duplicate);
    }

    public function test_invalid_task_without_command_throws_invalid_argument_exception(): void
    {
        $task = TaskDefinition::make('no-command-task')
            ->label('No Command');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "no-command-task" must define a command before registration.');

        $this->registry->register($task);
    }

    public function test_find_by_name_returns_null_for_unknown_name(): void
    {
        $task = TaskDefinition::make('existing-task')
            ->label('Existing Task')
            ->command('app:existing');

        $this->registry->register($task);

        $result = $this->registry->findByName('non-existent-task');

        $this->assertNull($result);
    }
}
