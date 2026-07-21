<?php

use App\Http\Middleware\JwtAuth;
use App\Http\Middleware\PrometheusMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('metrics:collect')
            ->everyFiveMinutes()
            ->sendOutputTo(storage_path('logs/metrics.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::warning('Failed to collect application metrics');
            });
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtAuth::class,
        ]);
        $middleware->api(append: [
            PrometheusMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
