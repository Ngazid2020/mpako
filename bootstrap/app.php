<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->trustProxies(at: '*');

        // Pas de route nommée `login` : les invités (routes web hors panels Filament,
        // ex. mode hors ligne, PDF) sont renvoyés vers la connexion du panel Commerce.
        $middleware->redirectGuestsTo('/commerce/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
