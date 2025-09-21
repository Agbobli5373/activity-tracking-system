<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @param  string|null  $guard
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission, string $guard = null)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user account is active and not locked
        if (!$user->isActive()) {
            Auth::logout();
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is inactive or locked.'], 403);
            }
            
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive or locked. Please contact an administrator.'
            ]);
        }

        // Check if user has the required permission
        if (!$user->hasPermissionTo($permission, $guard)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized permission access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'required_permission' => $permission,
                'guard' => $guard,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'user_roles' => $user->getRoleNames()->toArray(),
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Insufficient permissions. Required permission: ' . $permission
                ], 403);
            }

            abort(403, 'Unauthorized. You do not have the required permission to access this resource.');
        }

        return $next($request);
    }
}