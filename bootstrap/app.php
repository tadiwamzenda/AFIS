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
        //
    })
       ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Laravel already withholds 'password'/'password_confirmation' from
        // session flash data by default on validation failure. These
        // AFIS-specific fields carry the same sensitivity (a Navixy API
        // key/session hash) and need the same protection, or a failed
        // form submission (e.g. ClientForm's navixy_api_key field) could
        // leave a live credential sitting in session storage.
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'navixy_api_key',
            'api_key',
            'hash',
        ]);
    })->create();
