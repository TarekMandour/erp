<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Define your route redirects here
            Route::middleware(['web'])
            ->prefix('admin')
            ->name('admin.')
            ->namespace('App\Http\Controllers\Admin')
            ->group(base_path('routes/admin.php'));

            Route::middleware(['web'])
            ->prefix('company')
            ->name('company.')
            ->namespace('App\Http\Controllers\Company')
            ->group(base_path('routes/company.php'));

            Route::middleware(['web'])
            ->prefix('admin/finance')
            ->name('finance.')
            ->namespace('App\Http\Controllers\Finance')
            ->group(base_path('routes\finance.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'company' => \App\Http\Middleware\EnsureComapnyRole::class,
            'super' => \App\Http\Middleware\SuperCompanyUserMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
