<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    private const array ACTION_MAP = [
        'index' => 'FINDALL',
        'store' => 'CREATE',
        'show' => 'FINDONE',
        'update' => 'UPDATE',
        'destroy' => 'DELETE',
        'adjustStock' => 'ADJUSTSTOCK',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $controllerAction = $request->route()?->getAction('controller');

        if (! \is_string($controllerAction) || ! str_contains($controllerAction, '@')) {
            return $next($request);
        }

        [$controller, $method] = explode('@', $controllerAction);

        $permission = $this->resolvePermission(
            $controller,
            $method,
        );

        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function resolvePermission(string $controllerClass, string $method): string
    {
        $controller = class_basename($controllerClass);

        if ($controller == 'StatsController' && $method == 'show') {
            return 'STATS.SHOW';
        }
        $resource = Str::beforeLast($controller, 'Controller');

        $resource = Str::upper(Str::pluralStudly($resource));

        $action = self::ACTION_MAP[$method] ?? strtoupper($method);

        return "{$resource}.{$action}";
    }
}
