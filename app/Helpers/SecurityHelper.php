<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Sanitize string input for safe display.
     */
    public static function sanitizeString(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        return $input;
    }

    /**
     * Check if string contains potentially malicious content.
     */
    public static function containsMaliciousContent(string $input): bool
    {
        $maliciousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<form\b[^>]*>/i',
            '/data:text\/html/i',
            '/vbscript:/i',
            '/<meta\b[^>]*>/i',
            '/<link\b[^>]*>/i',
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/\'\s*(OR|AND)\s*\'/i',
            '/--/i',
            '/\/\*/i',
            '/\.\.[\/\\\\]/',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate secure random token.
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash sensitive data for logging.
     */
    public static function hashForLogging(string $data): string
    {
        return hash('sha256', $data);
    }

    /**
     * Validate file upload security.
     */
    public static function validateFileUpload(array $file): array
    {
        $errors = [];
        $allowedExtensions = config('security.validation.allowed_file_extensions', []);
        $maxFileSize = config('security.validation.max_file_size', 10240) * 1024; // Convert KB to bytes

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "File extension '{$extension}' is not allowed.";
        }

        // Check file size
        if ($file['size'] > $maxFileSize) {
            $errors[] = "File size exceeds maximum allowed size.";
        }

        // Check for double extensions
        if (substr_count($file['name'], '.') > 1) {
            $errors[] = "Files with multiple extensions are not allowed.";
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = "File type '{$mimeType}' is not allowed.";
        }

        return $errors;
    }

    /**
     * Log security event.
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        logger()->warning("Security Event: {$event}", array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Check if request is from a trusted source.
     */
    public static function isTrustedRequest(): bool
    {
        $request = request();
        
        // Check for proper referrer
        $referrer = $request->header('referer');
        if ($referrer && !str_starts_with($referrer, config('app.url'))) {
            return false;
        }

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return false;
            }
        }

        return true;
    }
}