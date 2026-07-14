<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    private MetricsService $metrics;

    public function __construct()
    {
        $this->metrics = MetricsService::getInstance();
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $route = $this->getRoutePattern($request);
        $method = $request->method();

        $gauge = $this->metrics->createGauge('http_requests_in_flight', 'Requests in flight', ['method']);
        $gauge->incBy(1, [$method]);

        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        $gauge->decBy(1, [$method]);

        $counter = $this->metrics->createCounter(
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status_code']
        );
        $counter->incBy(1, [$method, $route, (string) $response->getStatusCode()]);

        $histogram = $this->metrics->createHistogram(
            'http_request_duration_ms',
            'Request duration in ms',
            ['method', 'route']
        );
        $histogram->observe($duration, [$method, $route]);

        return $response;
    }

    private function shouldIgnore(Request $request): bool
    {
        $path = $request->path();
        foreach (config('prometheus.ignored_routes', []) as $pattern) {
            if (str($path)->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function getRoutePattern(Request $request): string
    {
        $route = $request->route();
        if ($route && method_exists($route, 'uri')) {
            return $route->uri();
        }

        return $request->path();
    }
}
