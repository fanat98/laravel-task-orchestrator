<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Registry\InMemoryTaskRegistry;
use Malsa\TaskOrchestrator\Support\TaskDownstreamResolver;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskDownstreamResolverTest extends TestCase
{
    private TaskOrchestratorManager $manager;

    private TaskDownstreamResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = new InMemoryTaskRegistry();
        $this->manager = new TaskOrchestratorManager($registry);
        $this->resolver = new TaskDownstreamResolver($this->manager);
    }

    public function test_returns_direct_dependents_of_given_task(): void
    {
        // task-b depends on task-a, so task-b is a direct dependent of task-a
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-a'])
        );
        $this->manager->register(
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
                ->dependsOn(['task-a'])
        );

        $result = $this->resolver->directDependentsOf('task-a');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        $this->assertCount(2, $result);
        $this->assertContains('task-b', $names);
        $this->assertContains('task-c', $names);
    }

    public function test_does_not_return_transitive_dependents(): void
    {
        // task-b depends on task-a, task-c depends on task-b
        // Only task-b is a direct dependent of task-a
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-a'])
        );
        $this->manager->register(
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
                ->dependsOn(['task-b'])
        );

        $result = $this->resolver->directDependentsOf('task-a');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        $this->assertCount(1, $result);
        $this->assertSame('task-b', $names[0]);
    }

    public function test_returns_empty_array_when_no_dependents(): void
    {
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
        );

        $result = $this->resolver->directDependentsOf('task-a');

        $this->assertSame([], $result);
    }

    public function test_returns_empty_array_for_unknown_task_name(): void
    {
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
        );

        $result = $this->resolver->directDependentsOf('non-existent');

        $this->assertSame([], $result);
    }

    public function test_results_sorted_by_group_order_then_order_then_label(): void
    {
        $this->manager->register(
            TaskDefinition::make('root-task')
                ->label('Root Task')
                ->command('app:root')
        );

        // Register dependents with varying groupOrder, order, and label
        $this->manager->register(
            TaskDefinition::make('task-z')
                ->label('Zebra Task')
                ->command('app:task-z')
                ->groupOrder(2)
                ->order(1)
                ->dependsOn(['root-task'])
        );
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Alpha Task')
                ->command('app:task-a')
                ->groupOrder(1)
                ->order(2)
                ->dependsOn(['root-task'])
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Beta Task')
                ->command('app:task-b')
                ->groupOrder(1)
                ->order(1)
                ->dependsOn(['root-task'])
        );

        $result = $this->resolver->directDependentsOf('root-task');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        // Sorted by groupOrder first (1, 1, 2), then order (1, 2), then label
        $this->assertSame(['task-b', 'task-a', 'task-z'], $names);
    }

    public function test_results_sorted_by_label_when_group_order_and_order_are_equal(): void
    {
        $this->manager->register(
            TaskDefinition::make('root-task')
                ->label('Root Task')
                ->command('app:root')
        );

        $this->manager->register(
            TaskDefinition::make('task-c')
                ->label('Charlie')
                ->command('app:task-c')
                ->groupOrder(1)
                ->order(1)
                ->dependsOn(['root-task'])
        );
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Alpha')
                ->command('app:task-a')
                ->groupOrder(1)
                ->order(1)
                ->dependsOn(['root-task'])
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Bravo')
                ->command('app:task-b')
                ->groupOrder(1)
                ->order(1)
                ->dependsOn(['root-task'])
        );

        $result = $this->resolver->directDependentsOf('root-task');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        // Same groupOrder and order, so sorted by label alphabetically
        $this->assertSame(['task-a', 'task-b', 'task-c'], $names);
    }

    public function test_null_group_order_and_order_sort_after_defined_values(): void
    {
        $this->manager->register(
            TaskDefinition::make('root-task')
                ->label('Root Task')
                ->command('app:root')
        );

        // Task with null groupOrder/order (defaults)
        $this->manager->register(
            TaskDefinition::make('task-null')
                ->label('Null Order Task')
                ->command('app:task-null')
                ->dependsOn(['root-task'])
        );

        // Task with explicit groupOrder/order
        $this->manager->register(
            TaskDefinition::make('task-explicit')
                ->label('Explicit Order Task')
                ->command('app:task-explicit')
                ->groupOrder(1)
                ->order(1)
                ->dependsOn(['root-task'])
        );

        $result = $this->resolver->directDependentsOf('root-task');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        // Explicit (groupOrder=1) should come before null (treated as 999999)
        $this->assertSame(['task-explicit', 'task-null'], $names);
    }
}
