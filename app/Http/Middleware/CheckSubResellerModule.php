<?php

namespace App\Http\Middleware;

use App\Support\ResellerContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side companion to the society menu filtering for a reseller's team login (CakePHP
 * AppController::_subResellerCan / _subResellerHasPermission): the menus only hide links, this stops the action.
 *
 * Usage: CheckSubResellerModule::class . ':module,action[,flag...]'
 *   action  add|edit|delete|generate|update|view, several joined with | (any one is enough), or "auto" =
 *           add when the route has no {id}, edit when it has one (Cake: empty($id) ? 'add' : 'edit')
 *   flags   post = only check POST/PUT/PATCH requests (a screen whose page is open to view but whose save is guarded)
 *           json = the caller is a fetch() that reads JSON, so answer with JSON instead of a redirect
 *
 * A reseller itself and a real Society login are never restricted (see ResellerContext::can).
 */
class CheckSubResellerModule
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view', string ...$flags): Response
    {
        if (in_array('post', $flags, true) && !in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        if ($action === 'auto') {
            $action = empty($request->route('id')) ? 'add' : 'edit';
        }

        if (ResellerContext::can($module, explode('|', $action))) {
            return $next($request);
        }

        $message = 'You do not have permission to do that.';

        if (in_array('json', $flags, true)) {
            return response()->json(['success' => false, 'error' => $message, 'message' => $message]);
        }

        return redirect()->route('society.dashboard')->with('error', $message);
    }
}
