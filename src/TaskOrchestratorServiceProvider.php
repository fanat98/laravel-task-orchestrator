<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Actions\RetryTaskRunAction;
use Malsa\TaskOrchestrator\Actions\StartTaskAction;
use Malsa\TaskOrchestrator\Console\Commands\RunScheduledTaskCommand;
use Malsa\TaskOrchestrator\Contracts\TaskRegistry;
use Malsa\TaskOrchestrator\Http\Middleware\AuthorizeTaskOrchestrator;
use Malsa\TaskOrchestrator\Registry\InMemoryTaskRegistry;
use Malsa\TaskOrchestrator\Support\CommandDiscoveryRegistrar;
use Malsa\TaskOrchestrator\Support\CurrentTaskRunStore;
use Malsa\TaskOrchestrator\Support\DiscoveredScheduleRegistrar;
use Malsa\TaskOrchestrator\Support\DiscoveredTaskDefinitionFactory;
use Malsa\TaskOrchestrator\Support\SystemHealthInspector;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Support\TaskProgressUpdater;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;

final class TaskOrchestratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/task-orchestrator.php',
            'task-orchestrator'
        );

        $this->app->singleton(TaskRegistry::class, InMemoryTaskRegistry::class);

        $this->app->singleton(TaskOrchestratorManager::class, function ($app): TaskOrchestratorManager {
            return new TaskOrchestratorManager(
                $app->make(TaskRegistry::class)
            );
        });

        $this->app->singleton(StartTaskAction::class, function ($app): StartTaskAction {
            return new StartTaskAction(
                $app->make(TaskOrchestratorManager::class)
            );
        });

        $this->app->singleton(RetryTaskRunAction::class, function ($app): RetryTaskRunAction {
            return new RetryTaskRunAction(
                $app->make(StartTaskAction::class)
            );
        });

        $this->app->singleton(DiscoveredTaskDefinitionFactory::class, function (): DiscoveredTaskDefinitionFactory {
            return new DiscoveredTaskDefinitionFactory();
        });

        $this->app->singleton(CommandDiscoveryRegistrar::class, function ($app): CommandDiscoveryRegistrar {
            return new CommandDiscoveryRegistrar(
                $app->make(TaskOrchestratorManager::class),
                $app->make(DiscoveredTaskDefinitionFactory::class)
            );
        });

        $this->app->singleton(ExecuteTaskRunAction::class, function ($app): ExecuteTaskRunAction {
            return new ExecuteTaskRunAction(
                $app->make(\Illuminate\Contracts\Console\Kernel::class),
                $app->make(CurrentTaskRunStore::class)
            );
        });

        $this->app->singleton(CurrentTaskRunStore::class, function (): CurrentTaskRunStore {
            return new CurrentTaskRunStore();
        });

        $this->app->singleton(TaskProgressUpdater::class, function ($app): TaskProgressUpdater {
            return new TaskProgressUpdater(
                $app->make(CurrentTaskRunStore::class)
            );
        });

        $this->app->singleton('task-orchestrator.progress', function ($app): TaskProgressUpdater {
            return $app->make(TaskProgressUpdater::class);
        });

        $this->app->singleton(SystemHealthInspector::class, function (): SystemHealthInspector {
            return new SystemHealthInspector();
        });

        $this->app->singleton(TaskScheduleCalculator::class, function (): TaskScheduleCalculator {
            return new TaskScheduleCalculator();
        });

        $this->app->singleton(DiscoveredScheduleRegistrar::class, function () {
            return new DiscoveredScheduleRegistrar();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/task-orchestrator.php' => config_path('task-orchestrator.php'),
        ], 'task-orchestrator-config');

        $this->publishes([
            __DIR__ . '/../public/build' => public_path('vendor/task-orchestrator/build'),
        ], 'task-orchestrator-assets');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'task-orchestrator');

        $this->app['router']->aliasMiddleware(
            'task-orchestrator.auth',
            \Malsa\TaskOrchestrator\Http\Middleware\AuthorizeTaskOrchestrator::class
        );

        $this->registerDiscoveredCommands();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Malsa\TaskOrchestrator\Console\Commands\RunScheduledTaskCommand::class,
            ]);
        }

        $this->app->booted(function () {
            if (! $this->app->runningInConsole()) {
                return;
            }

            $this->app->afterResolving(
                \Illuminate\Console\Scheduling\Schedule::class,
                function (\Illuminate\Console\Scheduling\Schedule $schedule) {
                    $tasks = $this->app->make(\Malsa\TaskOrchestrator\Support\TaskOrchestratorManager::class);
                    $registrar = $this->app->make(\Malsa\TaskOrchestrator\Support\DiscoveredScheduleRegistrar::class);

                    $registrar->register($schedule, $tasks);
                }
            );
        });
    }

    protected function registerRoutes(): void
    {
        $middleware = config('task-orchestrator.middleware', ['web']);

        $middleware[] = 'task-orchestrator.auth';

        $this->app['router']
            ->middleware($middleware)
            ->prefix(config('task-orchestrator.route_prefix', 'task-orchestrator'))
            ->as('task-orchestrator.')
            ->group(__DIR__ . '/../routes/web.php');
    }

    private function registerDiscoveredCommands(): void
    {
        $discoveryFile = config('task-orchestrator.discovery_path');

        if (! is_string($discoveryFile) || ! is_file($discoveryFile)) {
            return;
        }

        $config = require $discoveryFile;

        if (! is_array($config)) {
            return;
        }

        $commands = $config['commands'] ?? [];

        if (! is_array($commands) || $commands === []) {
            return;
        }

        $this->app->make(\Malsa\TaskOrchestrator\Support\CommandDiscoveryRegistrar::class)
            ->register($commands);
    }
}
