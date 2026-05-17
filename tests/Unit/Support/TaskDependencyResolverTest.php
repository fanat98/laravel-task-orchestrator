<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Registry\InMemoryTaskRegistry;
use Malsa\TaskOrchestrator\Support\TaskDependencyResolver;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskDependencyResolverTest extends TestCase
{
    private TaskOrchestratorManager $manager;

    private TaskDependencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = new InMemoryTaskRegistry();
        $this->manager = new TaskOrchestratorManager($registry);
        $this->resolver = new TaskDependencyResolver($this->manager);
    }

    public function test_resolves_task_with_all_transitive_dependencies_in_topological_order(): void
    {
        // A depends on B, B depends on C => resolved order: C, B, A
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b'])
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-c'])
        );
        $this->manager->register(
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
        );

        $result = $this->resolver->resolveWithDependencies('task-a');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        $this->assertCount(3, $result);
        $this->assertSame(['task-c', 'task-b', 'task-a'], $names);
    }

    public function test_throws_for_unregistered_task(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "non-existent" is not registered.');

        $this->resolver->resolveWithDependencies('non-existent');
    }

    public function test_handles_task_with_no_dependencies(): void
    {
        $this->manager->register(
            TaskDefinition::make('standalone-task')
                ->label('Standalone Task')
                ->command('app:standalone')
        );

        $result = $this->resolver->resolveWithDependencies('standalone-task');

        $this->assertCount(1, $result);
        $this->assertSame('standalone-task', $result[0]->name);
    }

    public function test_handles_diamond_dependencies_without_duplicates(): void
    {
        // Diamond: A depends on B and C, both B and C depend on D
        // Expected order: D, B, C, A (or D, C, B, A depending on iteration)
        $this->manager->register(
            TaskDefinition::make('task-a')
                ->label('Task A')
                ->command('app:task-a')
                ->dependsOn(['task-b', 'task-c'])
        );
        $this->manager->register(
            TaskDefinition::make('task-b')
                ->label('Task B')
                ->command('app:task-b')
                ->dependsOn(['task-d'])
        );
        $this->manager->register(
            TaskDefinition::make('task-c')
                ->label('Task C')
                ->command('app:task-c')
                ->dependsOn(['task-d'])
        );
        $this->manager->register(
            TaskDefinition::make('task-d')
                ->label('Task D')
                ->command('app:task-d')
        );

        $result = $this->resolver->resolveWithDependencies('task-a');

        $names = array_map(fn (TaskDefinition $t) => $t->name, $result);

        // D should appear only once (no duplicates)
        $this->assertCount(4, $result);
        $this->assertCount(4, array_unique($names));

        // D must come before B and C, and A must be last (topological order)
        $this->assertSame('task-d', $names[0]);
        $this->assertSame('task-a', $names[3]);

        // B and C must both be present between D and A
        $this->assertContains('task-b', $names);
        $this->assertContains('task-c', $names);
    }
}
