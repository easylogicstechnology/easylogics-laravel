<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind CloudPanel's nginx (SSL terminates at the proxy, forwarded to
        // php-fpm over HTTP). Trust the proxy so Laravel detects HTTPS correctly -
        // needed for secure session cookies and https:// URL generation.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SwitchDatabaseConnection::class,
        ]);

        // CakePHP compares the typed credentials exactly (no trimming); Laravel already leaves 'password'
        // alone, 'username' is added so a leading/trailing space is not silently dropped either.
        $middleware->trimStrings(except: ['username']);

        // CakePHP AuthComponent::_unauthenticated(): flash authError and land on the home page (the login
        // modal opens with it); an AJAX request just gets 403.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->ajax()) {
                abort(403);
            }

            $request->session()->flash('error', 'You must be logged in to view this page.');

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
