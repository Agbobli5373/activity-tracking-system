<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users and specific routes
        if (Auth::check() && $this->shouldLog($request)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     */
    private function shouldLog(Request $request): bool
    {
        // Don't log certain routes
        $excludedRoutes = [
            'debugbar.*',
            'telescope.*',
            '_ignition.*',
            'livewire.*',
        ];

        foreach ($excludedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return false;
            }
        }

        // Don't log GET requests to assets or API health checks
        if ($request->isMethod('GET')) {
            $excludedPaths = [
                '/css/',
                '/js/',
                '/images/',
                '/favicon.ico',
                '/health',
                '/ping',
            ];

            foreach ($excludedPaths as $path) {
                if (str_contains($request->getPathInfo(), $path)) {
                    return false;
                }
            }
        }

        // Log all POST, PUT, PATCH, DELETE requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // Log specific GET routes
        $loggedGetRoutes = [
            'dashboard',
            'activities.*',
            'reports.*',
            'users.*',
        ];

        foreach ($loggedGetRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request.
     */
    private function logRequest(Request $request, Response $response): void
    {
        $action = $this->determineAction($request, $response);
        
        if ($action) {
            AuditService::log($action, null, null, null, [
                'route' => $request->route()?->getName(),
                'status_code' => $response->getStatusCode(),
            ], $request);
        }
    }

    /**
     * Determine the action based on the request.
     */
    private function determineAction(Request $request, Response $response): ?string
    {
        $route = $request->route()?->getName();
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // Handle authentication routes
        if ($route === 'login' && $method === 'POST') {
            return $statusCode === 302 ? 'login_success' : 'login_failed';
        }

        if ($route === 'logout') {
            return 'logout';
        }

        // Handle activity routes
        if (str_starts_with($route, 'activities.')) {
            return match ($route) {
                'activities.index' => 'activities_viewed',
                'activities.show' => 'activity_viewed',
                'activities.create' => 'activity_create_form_viewed',
                'activities.store' => $statusCode < 400 ? 'activity_created' : 'activity_create_failed',
                'activities.edit' => 'activity_edit_form_viewed',
                'activities.update' => $statusCode < 400 ? 'activity_updated' : 'activity_update_failed',
                'activities.destroy' => $statusCode < 400 ? 'activity_deleted' : 'activity_delete_failed',
                default => null,
            };
        }

        // Handle report routes
        if (str_starts_with($route, 'reports.')) {
            return match ($route) {
                'reports.index' => 'reports_viewed',
                'reports.generate' => 'report_generated',
                'reports.export' => 'report_exported',
                default => null,
            };
        }

        // Handle dashboard
        if ($route === 'dashboard') {
            return 'dashboard_viewed';
        }

        // Handle user management
        if (str_starts_with($route, 'users.')) {
            return match ($route) {
                'users.index' => 'users_viewed',
                'users.show' => 'user_viewed',
                'users.create' => 'user_create_form_viewed',
                'users.store' => $statusCode < 400 ? 'user_created' : 'user_create_failed',
                'users.edit' => 'user_edit_form_viewed',
                'users.update' => $statusCode < 400 ? 'user_updated' : 'user_update_failed',
                default => null,
            };
        }

        return null;
    }
}