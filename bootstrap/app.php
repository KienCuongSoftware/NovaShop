<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'email.verified.otp' => \App\Http\Middleware\EnsureEmailOtpVerified::class,
        ]);
        $middleware->web(prepend: [
            \App\Http\Middleware\RedirectLocalhostToLoopbackIp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->guest(route('admin.login'));
            }

            return redirect()->guest(route('login'));
        });
    })->create();
