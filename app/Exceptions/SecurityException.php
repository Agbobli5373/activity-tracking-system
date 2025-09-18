<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecurityException extends Exception
{
    /**
     * The security violation type.
     */
    protected string $violationType;

    /**
     * Additional context for the security violation.
     */
    protected array $context;

    /**
     * Create a new security exception instance.
     */
    public function __construct(
        string $message = 'Security violation detected',
        string $violationType = 'general',
        array $context = [],
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->violationType = $violationType;
        $this->context = $context;
    }

    /**
     * Get the violation type.
     */
    public function getViolationType(): string
    {
        return $this->violationType;
    }

    /**
     * Get the violation context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): Response
    {
        // Log security violation
        logger()->warning('Security violation detected', [
            'type' => $this->violationType,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
        ]);

        // Return appropriate response based on request type
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Security violation detected. This incident has been logged.',
                'code' => 'SECURITY_VIOLATION'
            ], 403);
        }

        return response()->view('errors.security', [
            'message' => 'Security violation detected. This incident has been logged.',
            'type' => $this->violationType
        ], 403);
    }
}