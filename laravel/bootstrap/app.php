<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Authentication and request integrity are handled by the platform's
        // JWT middleware. The application API does not use Laravel forms.
        $middleware->validateCsrfTokens(except: ['*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Some shared hosts remove environment files outside the document root. A
// server-only PHP config beside the application is supported as a fallback.
$runtimeConfigPath = dirname(__DIR__).'/staging.runtime.php';
if (! is_file(dirname(__DIR__).'/.env') && is_file($runtimeConfigPath)) {
    $runtimeConfig = require $runtimeConfigPath;

    if (is_array($runtimeConfig)) {
        foreach ($runtimeConfig as $name => $value) {
            if (! is_string($name) || ! is_scalar($value)) {
                continue;
            }

            $value = (string) $value;
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

return $application;
