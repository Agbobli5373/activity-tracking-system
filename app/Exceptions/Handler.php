<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Enhanced logging for production
            if (app()->environment('production')) {
                $this->logProductionError($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Custom error pages for production
        if (app()->environment('production')) {
            return $this->renderProductionError($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Log production errors with enhanced context
     *
     * @param \Throwable $e
     * @return void
     */
    protected function logProductionError(Throwable $e)
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        // Log to security channel for authentication/authorization errors
        if ($this->isSecurityException($e)) {
            Log::channel('security')->error('Security Exception', $context);
        }

        // Log to performance channel for performance-related errors
        if ($this->isPerformanceException($e)) {
            Log::channel('performance')->warning('Performance Exception', $context);
        }

        // Log all errors to main channel
        Log::error('Production Exception', $context);
    }

    /**
     * Render production-friendly error responses
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderProductionError(Request $request, Throwable $e)
    {
        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getProductionErrorMessage($status),
                'error_id' => uniqid('err_'),
                'timestamp' => now()->toISOString(),
            ], $status);
        }

        // Return custom error views for web requests
        if (view()->exists("errors.{$status}")) {
            return response()->view("errors.{$status}", [
                'exception' => $e,
                'error_id' => uniqid('err_'),
            ], $status);
        }

        return parent::render($request, $e);
    }

    /**
     * Get production-friendly error message
     *
     * @param int $status
     * @return string
     */
    protected function getProductionErrorMessage(int $status): string
    {
        return match ($status) {
            401 => 'Authentication required.',
            403 => 'Access denied.',
            404 => 'Resource not found.',
            422 => 'The given data was invalid.',
            429 => 'Too many requests.',
            500 => 'Internal server error.',
            503 => 'Service temporarily unavailable.',
            default => 'An error occurred.',
        };
    }

    /**
     * Check if exception is security-related
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isSecurityException(Throwable $e): bool
    {
        return $e instanceof \Illuminate\Auth\AuthenticationException ||
               $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
               $e instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException ||
               $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
    }

    /**
     * Check if exception is performance-related
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function isPerformanceException(Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException ||
               $e instanceof \Illuminate\Http\Client\RequestException ||
               str_contains($e->getMessage(), 'timeout') ||
               str_contains($e->getMessage(), 'memory');
    }
}
