<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use Malsa\TaskOrchestrator\Support\CurrentTaskRunStore;
use Malsa\TaskOrchestrator\Tests\TestCase;

class CurrentTaskRunStoreTest extends TestCase
{
    private CurrentTaskRunStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new CurrentTaskRunStore();
    }

    public function test_initial_state_is_null(): void
    {
        $this->assertNull($this->store->get());
    }

    public function test_set_stores_value(): void
    {
        $this->store->set('run-123');

        $this->assertSame('run-123', $this->store->get());
    }

    public function test_get_retrieves_stored_value(): void
    {
        $this->store->set('run-abc');

        $this->assertSame('run-abc', $this->store->get());
    }

    public function test_clear_resets_to_null(): void
    {
        $this->store->set('run-456');
        $this->store->clear();

        $this->assertNull($this->store->get());
    }
}
