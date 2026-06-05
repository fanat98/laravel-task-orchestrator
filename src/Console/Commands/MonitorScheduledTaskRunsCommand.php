<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Illuminate\Console\Command;
use Malsa\TaskOrchestrator\Support\ScheduledTaskMonitor;

final class MonitorScheduledTaskRunsCommand extends Command
{
    protected $signature = 'task-orchestrator:monitor-scheduled-runs
        {--grace-minutes= : Override grace period in minutes after the cron due time}
        {--fail-on-missed= : Return non-zero exit code when missed tasks are detected}';

    protected $description = 'Checks scheduled tasks and reports tasks that missed their expected run window';

    public function __construct(
        private readonly ScheduledTaskMonitor $monitor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! (bool) config('task-orchestrator.scheduled_monitoring.enabled', true)) {
            $this->info('Scheduled task monitoring is disabled by configuration.');

            return self::SUCCESS;
        }

        $graceMinutes = $this->resolveGraceMinutesOption();
        $result = $this->monitor->inspect($graceMinutes);

        if ($result['missed_count'] === 0) {
            $this->info(sprintf(
                'Scheduled monitoring healthy: checked %d task(s), grace %d minute(s).',
                $result['checked_tasks'],
                $result['grace_minutes']
            ));

            return self::SUCCESS;
        }

        $this->error(sprintf('Found %d missed scheduled task(s).', $result['missed_count']));

        foreach ($result['missed_tasks'] as $task) {
            $this->error(sprintf(
                '- [%s] due_at=%s last_run=%s overdue=%dmin',
                $task['task_name'],
                $task['last_due_at'],
                $task['last_scheduled_run_at'] ?? 'never',
                $task['minutes_overdue']
            ));
        }

        return $this->shouldFailOnMissed() ? self::FAILURE : self::SUCCESS;
    }

    private function shouldFailOnMissed(): bool
    {
        $configured = (bool) config('task-orchestrator.scheduled_monitoring.fail_command_on_missed', true);
        $option = $this->option('fail-on-missed');

        if ($option === null) {
            return $configured;
        }

        $parsed = filter_var($option, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $configured;
    }

    private function resolveGraceMinutesOption(): ?int
    {
        $option = $this->option('grace-minutes');

        if ($option === null || trim((string) $option) === '') {
            return null;
        }

        if (! is_numeric($option)) {
            $this->warn('Invalid --grace-minutes option. Falling back to configured grace.');

            return null;
        }

        return max((int) $option, 0);
    }
}

