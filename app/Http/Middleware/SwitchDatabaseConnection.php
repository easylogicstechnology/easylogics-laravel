<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SwitchDatabaseConnection
{
    protected array $allowedConnections = ['mysql', 'database201920'];

    public function handle(Request $request, Closure $next): Response
    {
        $connection = $request->session()->get('db_connection', 'mysql');

        if (!in_array($connection, $this->allowedConnections)) {
            $connection = 'mysql';
        }

        Config::set('database.default', $connection);
        DB::purge($connection);

        return $next($request);
    }
}
