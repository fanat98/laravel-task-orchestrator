<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeTaskOrchestrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('task-orchestrator.authorization.enabled', true)) {
            return $next($request);
        }

        $mode = config('task-orchestrator.authorization.mode', 'gate');
        $message = config(
            'task-orchestrator.authorization.forbidden_message',
            'You do not have permission to access Task Orchestrator.'
        );

        if ($mode === 'user_field') {
            $user = $request->user();
            $field = config('task-orchestrator.authorization.user_field', 'is_admin');

            if ($user === null || ! (bool) data_get($user, $field)) {
                throw new AuthorizationException($message);
            }

            return $next($request);
        }

        $gate = config('task-orchestrator.authorization.gate', 'viewTaskOrchestrator');

        try {
            Gate::authorize($gate);
        } catch (AuthorizationException $exception) {
            throw new AuthorizationException($message, $exception->getCode(), $exception);
        }

        return $next($request);
    }
}
