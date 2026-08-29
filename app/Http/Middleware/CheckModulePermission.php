<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Usage: CheckModulePermission::class . ':module,action'
     * e.g. CheckModulePermission::class . ':members,can_view'
     *      CheckModulePermission::class . ':bills,can_add'
     *
     * Reseller gets full access (bypass). ResellerUser checked against user_permissions table.
     * All other roles pass through (their own middleware handles access).
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'can_view'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->role !== 'ResellerUser') {
            return $next($request);
        }

        if (!$user->hasPermission($module, $action)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
