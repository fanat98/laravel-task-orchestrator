<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Malsa\TaskOrchestrator\Http\Controllers\DashboardController;
use Malsa\TaskOrchestrator\Http\Controllers\FailedTaskRunIndexController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskIndexController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskRetryController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskRunIndexController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskRunShowController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskStartController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskRunStatusController;
use Malsa\TaskOrchestrator\Http\Controllers\TaskRunLogsController;

Route::get('/', DashboardController::class)
    ->name('dashboard');

Route::get('/tasks', TaskIndexController::class)
    ->name('tasks.index');

Route::post('/tasks/{task}/run', TaskStartController::class)
    ->name('tasks.run');

Route::get('/runs', TaskRunIndexController::class)
    ->name('runs.index');

Route::get('/failed-runs', FailedTaskRunIndexController::class)
    ->name('runs.failed');

Route::get('/runs/{taskRun}', TaskRunShowController::class)
    ->name('runs.show');

Route::post('/runs/{taskRun}/retry', TaskRetryController::class)
    ->name('runs.retry');

Route::get('/api/runs/{taskRun}', TaskRunStatusController::class)
    ->name('api.runs.show');

Route::get('/api/runs/{taskRun}/logs', TaskRunLogsController::class)
    ->name('api.runs.logs');

Route::get('/api/dashboard', \Malsa\TaskOrchestrator\Http\Controllers\DashboardStatusController::class)
    ->name('api.dashboard');
