<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\DiscoveredTaskDefinitionFactory;
use Malsa\TaskOrchestrator\Tests\TestCase;

class DiscoveredTaskDefinitionFactoryTest extends TestCase
{
    private DiscoveredTaskDefinitionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DiscoveredTaskDefinitionFactory();
    }

    public function test_from_command_creates_valid_definition_with_defaults(): void
    {
        $definition = $this->factory->fromCommand('app:import-users');

        $this->assertInstanceOf(TaskDefinition::class, $definition);
        $this->assertTrue($definition->isValid());
        $this->assertSame('app:import-users', $definition->command);
        $this->assertNull($definition->description);
        $this->assertNull($definition->group);
        $this->assertNull($definition->groupOrder);
        $this->assertNull($definition->order);
        $this->assertSame([], $definition->dependsOn);
        $this->assertNull($definition->timeoutMinutes);
        $this->assertNull($definition->queue);
        $this->assertNull($definition->connection);
        $this->assertNull($definition->schedule);
        $this->assertNull($definition->notifications);
    }

    public function test_from_command_respects_all_metadata_fields(): void
    {
        $metadata = [
            'name' => 'custom-name',
            'label' => 'Custom Label',
            'description' => 'A custom description',
            'group' => 'imports',
            'group_order' => 2,
            'order' => 5,
            'depends_on' => ['task-a', 'task-b'],
            'timeout_minutes' => 30,
            'queue' => 'high',
            'connection' => 'redis',
            'schedule' => ['expression' => '*/5 * * * *', 'human' => 'Every 5 minutes'],
            'notifications' => ['enabled' => true, 'recipients' => ['test@example.com']],
        ];

        $definition = $this->factory->fromCommand('app:import-users', $metadata);

        $this->assertSame('custom-name', $definition->name);
        $this->assertSame('Custom Label', $definition->label);
        $this->assertSame('A custom description', $definition->description);
        $this->assertSame('app:import-users', $definition->command);
        $this->assertSame('imports', $definition->group);
        $this->assertSame(2, $definition->groupOrder);
        $this->assertSame(5, $definition->order);
        $this->assertSame(['task-a', 'task-b'], $definition->dependsOn);
        $this->assertSame(30, $definition->timeoutMinutes);
        $this->assertSame('high', $definition->queue);
        $this->assertSame('redis', $definition->connection);
        $this->assertSame(['expression' => '*/5 * * * *', 'human' => 'Every 5 minutes'], $definition->schedule);
        $this->assertSame(['enabled' => true, 'recipients' => ['test@example.com']], $definition->notifications);
    }

    public function test_generates_name_from_command_when_not_provided(): void
    {
        $definition = $this->factory->fromCommand('app:import-users');

        $this->assertSame('app-import-users', $definition->name);
    }

    public function test_generates_label_from_command_when_not_provided(): void
    {
        $definition = $this->factory->fromCommand('app:import-users');

        $this->assertSame('App Import Users', $definition->label);
    }

    public function test_throws_on_invalid_queue_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid queue metadata');

        $this->factory->fromCommand('app:test', ['queue' => 123]);
    }

    public function test_throws_on_invalid_connection_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid connection metadata');

        $this->factory->fromCommand('app:test', ['connection' => 456]);
    }
}
