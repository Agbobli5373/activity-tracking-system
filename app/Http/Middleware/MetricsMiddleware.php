<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    private MetricsService $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // Get route name or URI
        $route = $request->route() ? $request->route()->getName() : $request->path();
        $method = $request->method();
        $status = (string) $response->getStatusCode();

        // Record metrics
        $this->metricsService->incrementRequestCounter($method, $route, $status);
        $this->metricsService->observeResponseTime($method, $route, $duration);

        // Record errors (4xx and 5xx status codes)
        if ($response->getStatusCode() >= 400) {
            $this->metricsService->incrementErrorCounter($method, $route, $status);
        }

        return $response;
    }
}
