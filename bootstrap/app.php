<?php

use App\Http\Middleware\EnsurePostOwnership;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [HandleInertiaRequests::class]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'post.owner' => EnsurePostOwnership::class,
        ]);

        // Pengguna yang sudah login & membuka /login atau /register diarahkan ke area masing-masing.
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->user()->isAdmin() ? '/admin/users' : '/dashboard/posts'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
