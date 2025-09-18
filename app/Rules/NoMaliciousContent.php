<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoMaliciousContent implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        // Check for common XSS patterns
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
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Log security violation
                logger()->warning('XSS attempt detected', [
                    'attribute' => $attribute,
                    'pattern_matched' => $pattern,
                    'value_length' => strlen($value),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'user_id' => auth()->id(),
                ]);
                
                $fail('The :attribute contains potentially malicious content.');
                return;
            }
        }

        // Check for SQL injection patterns
        $sqlPatterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/\'\s*(OR|AND)\s*\'/i',
            '/--/i',
            '/\/\*/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Log security violation
                logger()->warning('SQL injection attempt detected', [
                    'attribute' => $attribute,
                    'pattern_matched' => $pattern,
                    'value_length' => strlen($value),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'user_id' => auth()->id(),
                ]);
                
                $fail('The :attribute contains potentially harmful content.');
                return;
            }
        }

        // Check for path traversal attempts
        if (preg_match('/\.\.[\/\\\\]/', $value)) {
            // Log security violation
            logger()->warning('Path traversal attempt detected', [
                'attribute' => $attribute,
                'value_length' => strlen($value),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
            
            $fail('The :attribute contains invalid path characters.');
        }
    }
}