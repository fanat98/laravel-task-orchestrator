<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Domain;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskDefinitionTest extends TestCase
{
    public function test_make_creates_instance_with_defaults(): void
    {
        $definition = TaskDefinition::make('import-users');

        $this->assertInstanceOf(TaskDefinition::class, $definition);
        $this->assertSame('import-users', $definition->name);
        $this->assertSame('import-users', $definition->label);
        $this->assertNull($definition->command);
        $this->assertNull($definition->description);
        $this->assertSame([], $definition->arguments);
        $this->assertNull($definition->group);
        $this->assertNull($definition->groupOrder);
        $this->assertNull($definition->order);
        $this->assertNull($definition->schedule);
        $this->assertSame([], $definition->dependsOn);
        $this->assertNull($definition->timeoutMinutes);
        $this->assertTrue($definition->allowManualRun);
        $this->assertFalse($definition->allowConcurrentRuns);
        $this->assertNull($definition->queue);
        $this->assertNull($definition->connection);
        $this->assertNull($definition->notifications);
    }

    public function test_all_setter_methods_return_self_instance(): void
    {
        $definition = TaskDefinition::make('test-task');

        $this->assertInstanceOf(TaskDefinition::class, $definition->label('Test Task'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->description('A description'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->command('app:test'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->arguments(['--force']));
        $this->assertInstanceOf(TaskDefinition::class, $definition->group('imports'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->groupOrder(1));
        $this->assertInstanceOf(TaskDefinition::class, $definition->order(2));
        $this->assertInstanceOf(TaskDefinition::class, $definition->schedule(['expression' => '* * * * *']));
        $this->assertInstanceOf(TaskDefinition::class, $definition->dependsOn(['other-task']));
        $this->assertInstanceOf(TaskDefinition::class, $definition->timeoutMinutes(30));
        $this->assertInstanceOf(TaskDefinition::class, $definition->allowManualRun(false));
        $this->assertInstanceOf(TaskDefinition::class, $definition->allowConcurrentRuns(true));
        $this->assertInstanceOf(TaskDefinition::class, $definition->queue('high'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->connection('redis'));
        $this->assertInstanceOf(TaskDefinition::class, $definition->notifications(['enabled' => true, 'recipients' => ['test@example.com']]));
    }

    public function test_is_valid_returns_false_without_command(): void
    {
        $definition = TaskDefinition::make('test-task');

        $this->assertFalse($definition->isValid());
    }

    public function test_is_valid_returns_true_with_command(): void
    {
        $definition = TaskDefinition::make('test-task')
            ->command('app:test');

        $this->assertTrue($definition->isValid());
    }

    public function test_ensure_valid_throws_without_command(): void
    {
        $definition = TaskDefinition::make('test-task');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "test-task" must define a command before registration.');

        $definition->ensureValid();
    }

    public function test_ensure_valid_passes_with_command(): void
    {
        $definition = TaskDefinition::make('test-task')
            ->command('app:test');

        $definition->ensureValid();

        $this->addToAssertionCount(1);
    }

    public function test_empty_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task name cannot be empty.');

        TaskDefinition::make('');
    }

    public function test_empty_label_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task label cannot be empty.');

        TaskDefinition::make('test-task')->label('');
    }

    public function test_depends_on_setter_works_correctly(): void
    {
        $definition = TaskDefinition::make('test-task')
            ->dependsOn(['task-a', 'task-b']);

        $this->assertSame(['task-a', 'task-b'], $definition->dependsOn);
    }

    public function test_schedule_setter_works_correctly(): void
    {
        $schedule = ['expression' => '0 * * * *', 'human' => 'Every hour'];

        $definition = TaskDefinition::make('test-task')
            ->schedule($schedule);

        $this->assertSame($schedule, $definition->schedule);
    }

    public function test_notifications_setter_works_correctly(): void
    {
        $notifications = ['enabled' => true, 'recipients' => ['admin@example.com']];

        $definition = TaskDefinition::make('test-task')
            ->notifications($notifications);

        $this->assertSame($notifications, $definition->notifications);
    }

    public function test_builder_is_immutable(): void
    {
        $original = TaskDefinition::make('test-task');
        $withCommand = $original->command('app:test');

        $this->assertNull($original->command);
        $this->assertSame('app:test', $withCommand->command);
        $this->assertNotSame($original, $withCommand);
    }
}
