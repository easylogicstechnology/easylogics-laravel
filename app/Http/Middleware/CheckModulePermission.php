<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Usage: CheckModulePermission::class . ':module,action'
     * e.g. CheckModulePermission::class . ':software,view'
     *      CheckModulePermission::class . ':software,add'
     *
     * Reseller gets full access (bypass). SubReseller (a reseller's team login) is checked against the
     * {module}_{action} flags of its reseller_sub_users row. All other roles pass through (their own
     * middleware handles access).
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->role !== 'SubReseller') {
            return $next($request);
        }

        if (!$user->hasPermission($module, $action)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
