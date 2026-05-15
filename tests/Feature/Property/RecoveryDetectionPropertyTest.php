<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature: task-mail-notifications, Property 7: Recovery notification condition
 *
 * Validates: Requirements 4.1, 4.2, 4.3
 *
 * For any task run that transitions to Succeeded status, a recovery notification
 * shall be sent if and only if the most recent previous completed run (terminal status,
 * same task_name, excluding current) had Failed status. If the previous run had
 * Succeeded or Cancelled status, or no previous run exists, no recovery notification
 * shall be sent.
 */
class RecoveryDetectionPropertyTest extends TestCase
{
    use BlackBox;
    use RefreshDatabase;

    private RecoveryDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new RecoveryDetector();
    }

    /**
     * Property 7a: Recovery IS detected when the most recent previous completed run
     * had Failed status.
     *
     * **Validates: Requirements 4.1**
     *
     * @group property
     */
    public function test_recovery_is_detected_when_previous_run_failed(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->historicalRunCountSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $historicalRunCount) {
                // Create some older historical runs (all terminal) to add noise
                $baseTime = now()->subHours($historicalRunCount + 2);

                for ($i = 0; $i < $historicalRunCount; $i++) {
                    $this->createTaskRun(
                        $taskName,
                        $this->randomTerminalStatus(),
                        $baseTime->copy()->addMinutes($i * 10),
                    );
                }

                // Create the most recent previous run with Failed status
                $previousFailedRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Failed,
                    now()->subMinutes(30),
                );

                // Create the current successful run
                $currentRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                $this->assertNotNull(
                    $result,
                    "Recovery should be detected when previous run was Failed (task: {$taskName})",
                );
                $this->assertEquals(
                    $previousFailedRun->id,
                    $result->id,
                    'Detected recovery should reference the previous failed run',
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 7b: Recovery is NOT detected when the most recent previous completed
     * run had Succeeded status.
     *
     * **Validates: Requirements 4.2**
     *
     * @group property
     */
    public function test_no_recovery_when_previous_run_succeeded(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->historicalRunCountSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $historicalRunCount) {
                // Create some older historical runs to add noise
                $baseTime = now()->subHours($historicalRunCount + 2);

                for ($i = 0; $i < $historicalRunCount; $i++) {
                    $this->createTaskRun(
                        $taskName,
                        $this->randomTerminalStatus(),
                        $baseTime->copy()->addMinutes($i * 10),
                    );
                }

                // Create the most recent previous run with Succeeded status
                $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now()->subMinutes(30),
                );

                // Create the current successful run
                $currentRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                $this->assertNull(
                    $result,
                    "No recovery should be detected when previous run was Succeeded (task: {$taskName})",
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 7c: Recovery is NOT detected when the most recent previous completed
     * run had Cancelled status.
     *
     * **Validates: Requirements 4.2**
     *
     * @group property
     */
    public function test_no_recovery_when_previous_run_cancelled(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->historicalRunCountSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $historicalRunCount) {
                // Create some older historical runs to add noise
                $baseTime = now()->subHours($historicalRunCount + 2);

                for ($i = 0; $i < $historicalRunCount; $i++) {
                    $this->createTaskRun(
                        $taskName,
                        $this->randomTerminalStatus(),
                        $baseTime->copy()->addMinutes($i * 10),
                    );
                }

                // Create the most recent previous run with Cancelled status
                $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Cancelled,
                    now()->subMinutes(30),
                );

                // Create the current successful run
                $currentRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                $this->assertNull(
                    $result,
                    "No recovery should be detected when previous run was Cancelled (task: {$taskName})",
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 7d: Recovery is NOT detected when no previous completed run exists.
     *
     * **Validates: Requirements 4.3**
     *
     * @group property
     */
    public function test_no_recovery_when_no_previous_run_exists(): void
    {
        self::forAll($this->taskNameSet())
            ->take(100)
            ->then(function (string $taskName) {
                // Create only the current successful run (no previous runs)
                $currentRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                $this->assertNull(
                    $result,
                    "No recovery should be detected when no previous run exists (task: {$taskName})",
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 7e: Non-terminal previous runs (Pending, Queued, Running) are ignored
     * when determining recovery. Only terminal statuses are considered.
     *
     * **Validates: Requirements 4.1, 4.2, 4.3**
     *
     * @group property
     */
    public function test_non_terminal_runs_are_ignored_for_recovery_detection(): void
    {
        self::forAll(
            $this->taskNameSet(),
            Set::of(
                TaskRunStatus::Pending,
                TaskRunStatus::Queued,
                TaskRunStatus::Running,
            ),
        )
            ->take(100)
            ->then(function (string $taskName, TaskRunStatus $nonTerminalStatus) {
                // Create a non-terminal run as the "most recent" by time
                $this->createTaskRun(
                    $taskName,
                    $nonTerminalStatus,
                    now()->subMinutes(10),
                );

                // Create a failed terminal run before the non-terminal one
                $failedRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Failed,
                    now()->subMinutes(30),
                );

                // Create the current successful run
                $currentRun = $this->createTaskRun(
                    $taskName,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                // The non-terminal run should be ignored; the failed run is the
                // most recent terminal run, so recovery should be detected
                $this->assertNotNull(
                    $result,
                    "Non-terminal runs should be ignored; recovery should detect the failed run (task: {$taskName})",
                );
                $this->assertEquals(
                    $failedRun->id,
                    $result->id,
                    'Recovery should reference the most recent terminal (failed) run, ignoring non-terminal runs',
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 7f: Recovery detection is scoped to the same task_name.
     * Runs from other tasks do not affect recovery detection.
     *
     * **Validates: Requirements 4.1, 4.2, 4.3**
     *
     * @group property
     */
    public function test_recovery_detection_is_scoped_to_same_task_name(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->taskNameSet(),
        )
            ->filter(fn (string $taskA, string $taskB) => $taskA !== $taskB)
            ->take(100)
            ->then(function (string $taskA, string $taskB) {
                // Create a failed run for a DIFFERENT task
                $this->createTaskRun(
                    $taskB,
                    TaskRunStatus::Failed,
                    now()->subMinutes(30),
                );

                // Create the current successful run for taskA (no previous runs for taskA)
                $currentRun = $this->createTaskRun(
                    $taskA,
                    TaskRunStatus::Succeeded,
                    now(),
                );

                $result = $this->detector->detect($currentRun);

                $this->assertNull(
                    $result,
                    "Recovery should not be detected based on runs from a different task (taskA: {$taskA}, taskB: {$taskB})",
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskA)->delete();
                TaskRunRecord::where('task_name', $taskB)->delete();
            });
    }

    // --- Helper methods ---

    /**
     * Generate a set of unique task names.
     */
    private function taskNameSet(): Set
    {
        return Set::strings()
            ->madeOf(
                Set::strings()->chars()->lowercaseLetter(),
                Set::strings()->chars()->number(),
                Set::of('-'),
            )
            ->between(3, 30)
            ->filter(fn (string $s) => preg_match('/^[a-z][a-z0-9\-]+$/', $s) === 1);
    }

    /**
     * Generate a set of historical run counts (0 to 5 older runs).
     */
    private function historicalRunCountSet(): Set
    {
        return Set::integers()->between(0, 5)->toSet();
    }

    /**
     * Get a random terminal status for historical noise runs.
     */
    private function randomTerminalStatus(): TaskRunStatus
    {
        $statuses = [
            TaskRunStatus::Succeeded,
            TaskRunStatus::Failed,
            TaskRunStatus::Cancelled,
        ];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Create a TaskRunRecord with the given parameters.
     */
    private function createTaskRun(
        string $taskName,
        TaskRunStatus $status,
        \DateTimeInterface $finishedAt,
    ): TaskRunRecord {
        return TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => ucfirst(str_replace('-', ' ', $taskName)),
            'command' => "app:task:{$taskName}",
            'status' => $status->value,
            'started_at' => (clone $finishedAt)->modify('-5 minutes'),
            'finished_at' => $finishedAt,
            'failure_message' => $status === TaskRunStatus::Failed ? 'Test failure message' : null,
        ]);
    }
}
