<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Console;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Registry\InMemoryTaskRegistry;
use Malsa\TaskOrchestrator\Support\DiscoveredScheduleRegistrar;
use Malsa\TaskOrchestrator\Support\ScheduledArtisanCallback;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Ensures the scheduler triggers tasks in-process (no shell), e.g. for Docker Hardened Images.
 */
class ShellFreeSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private const RUN_SCHEDULED_TASK = 'task-orchestrator:run-scheduled-task';

    public function test_discovered_task_is_registered_as_callback_without_shell_command(): void
    {
        $event = $this->registerAndFind($this->scheduledTask('nightly-import'));

        $this->assertInstanceOf(CallbackEvent::class, $event);
        $this->assertNull($event->command, 'Scheduled task must not be registered as a shell command.');
        $this->assertSame('0 12,19 * * 1-5', $event->expression);

        $callback = $this->callbackOf($event);
        $this->assertSame(self::RUN_SCHEDULED_TASK, $callback->command);
        $this->assertSame(['task' => 'nightly-import'], $callback->parameters);
    }

    public function test_task_name_is_passed_verbatim_without_shell_escaping(): void
    {
        $event = $this->registerAndFind($this->scheduledTask("odd name 'quoted'"));

        $this->assertSame(['task' => "odd name 'quoted'"], $this->callbackOf($event)->parameters);
    }

    public function test_without_overlapping_uses_stable_unique_name(): void
    {
        $first = $this->registerAndFind($this->scheduledTask('nightly-import'));
        $second = $this->findEvent('nightly-import');
        $other = $this->registerAndFind($this->scheduledTask('other-import'));

        $this->assertTrue($first->withoutOverlapping);
        $this->assertSame(self::RUN_SCHEDULED_TASK . ' nightly-import', $first->description);
        $this->assertSame($first->mutexName(), $second->mutexName());
        $this->assertNotSame($first->mutexName(), $other->mutexName());
    }

    public function test_concurrent_task_does_not_use_without_overlapping(): void
    {
        $event = $this->registerAndFind($this->scheduledTask('parallel')->allowConcurrentRuns());

        $this->assertFalse($event->withoutOverlapping);
    }

    public function test_task_without_schedule_is_not_registered(): void
    {
        $schedule = new Schedule();
        $manager = new TaskOrchestratorManager(new InMemoryTaskRegistry());
        $manager->register(TaskDefinition::make('manual')->command('app:manual'));

        (new DiscoveredScheduleRegistrar())->register($schedule, $manager);

        $this->assertSame([], $schedule->events());
    }

    public function test_package_maintenance_events_are_registered_without_shell_commands(): void
    {
        $events = collect($this->app->make(Schedule::class)->events())
            ->keyBy(fn (Event $event) => $event->description);

        $recover = $events->get('task-orchestrator:recover-stale-runs');
        $heartbeat = $events->get('task-orchestrator:record-scheduler-heartbeat');

        $this->assertInstanceOf(CallbackEvent::class, $recover);
        $this->assertInstanceOf(CallbackEvent::class, $heartbeat);
        $this->assertTrue($recover->withoutOverlapping);
        $this->assertSame('*/10 * * * *', $recover->expression);
        $this->assertSame('* * * * *', $heartbeat->expression);

        foreach ($events as $event) {
            $this->assertNull($event->command, sprintf('Event [%s] must not use a shell command.', $event->description));
        }
    }

    public function test_non_zero_exit_code_marks_scheduled_event_as_failed(): void
    {
        Log::spy();

        $event = $this->runExitCodeEvent(2);

        $this->assertSame(1, $event->exitCode);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_zero_exit_code_marks_scheduled_event_as_successful(): void
    {
        Log::spy();

        $event = $this->runExitCodeEvent(0);

        $this->assertSame(0, $event->exitCode);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_scheduled_callback_dispatches_task_when_queue_worker_is_healthy(): void
    {
        Queue::fake();
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->toIso8601String(), now()->addDay());

        $event = $this->registerAndFind($this->scheduledTask('nightly-import'));
        $event->run($this->app);

        $this->assertSame(0, $event->exitCode);
        Queue::assertPushed(ExecuteTaskRunJob::class);
    }

    public function test_scheduled_callback_records_failure_when_queue_worker_is_down(): void
    {
        Queue::fake();
        Cache::forget('task_orchestrator.queue_worker_heartbeat');

        $event = $this->registerAndFind($this->scheduledTask('nightly-import'));
        $event->run($this->app);

        $this->assertSame(0, $event->exitCode);
        Queue::assertNothingPushed();

        $run = TaskRunRecord::query()->where('task_name', 'nightly-import')->first();
        $this->assertNotNull($run);
        $this->assertSame(TaskRunStatus::Failed->value, $run->status);
        $this->assertSame('scheduled', $run->trigger_type);
    }

    private function scheduledTask(string $name): TaskDefinition
    {
        return TaskDefinition::make($name)
            ->command('app:import')
            ->schedule(['expression' => '0 12,19 * * 1-5', 'human' => 'test']);
    }

    private function registerAndFind(TaskDefinition $task): CallbackEvent
    {
        $this->app->make(TaskOrchestratorManager::class)->register($task);

        return $this->findEvent($task->name);
    }

    private function findEvent(string $taskName): CallbackEvent
    {
        $schedule = new Schedule();
        (new DiscoveredScheduleRegistrar())->register($schedule, $this->app->make(TaskOrchestratorManager::class));

        $event = collect($schedule->events())
            ->first(fn (Event $event) => $event->description === self::RUN_SCHEDULED_TASK . ' ' . $taskName);

        $this->assertInstanceOf(CallbackEvent::class, $event);

        return $event;
    }

    private function runExitCodeEvent(int $exitCode): CallbackEvent
    {
        Artisan::command('test:exit-code {code}', fn (string $code): int => (int) $code);

        $event = (new Schedule())
            ->call(new ScheduledArtisanCallback('test:exit-code', ['code' => (string) $exitCode]))
            ->name('test:exit-code');
        $event->run($this->app);

        return $event;
    }

    private function callbackOf(CallbackEvent $event): ScheduledArtisanCallback
    {
        $callback = (new \ReflectionProperty(CallbackEvent::class, 'callback'))->getValue($event);
        $this->assertInstanceOf(ScheduledArtisanCallback::class, $callback);

        return $callback;
    }
}

