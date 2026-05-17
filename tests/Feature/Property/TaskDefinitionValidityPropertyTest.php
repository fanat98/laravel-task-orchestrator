<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for TaskDefinition validity invariants.
 *
 * **Validates: Requirements 10.8**
 */
class TaskDefinitionValidityPropertyTest extends TestCase
{
    use BlackBox;

    /**
     * Property: for all definitions with name, label, and command set,
     * isValid() returns true.
     *
     * **Validates: Requirements 10.8**
     *
     * @group property
     */
    public function test_definitions_with_required_fields_always_valid(): void
    {
        self::forAll(
            Set::strings()->between(1, 100),
            Set::strings()->between(1, 100),
            Set::strings()->between(1, 100),
        )
            ->filter(fn (string $name, string $label, string $command) => trim($name) !== '' && trim($label) !== '' && trim($command) !== '')
            ->take(200)
            ->then(function (string $name, string $label, string $command) {
                $definition = TaskDefinition::make($name)
                    ->label($label)
                    ->command($command);

                $this->assertTrue($definition->isValid());
                $this->assertSame($name, $definition->name);
                $this->assertSame($label, $definition->label);
                $this->assertSame($command, $definition->command);
            });
    }
}
