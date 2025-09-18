<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | Activity Tracking System application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'timeout' => env('SESSION_TIMEOUT', 1800), // 30 minutes in seconds
        'warning_time' => env('SESSION_WARNING_TIME', 300), // 5 minutes before timeout
        'secure_cookies' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'strict',
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'max_input_length' => env('MAX_INPUT_LENGTH', 2000),
        'max_filename_length' => env('MAX_FILENAME_LENGTH', 255),
        'allowed_file_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
        'max_file_size' => env('MAX_FILE_SIZE', 10240), // 10MB in KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'login_attempts' => env('LOGIN_RATE_LIMIT', 5),
        'login_decay_minutes' => env('LOGIN_DECAY_MINUTES', 15),
        'api_requests_per_minute' => env('API_RATE_LIMIT', 60),
        'report_generation_per_hour' => env('REPORT_RATE_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */
    'csp' => [
        'default_src' => "'self'",
        'script_src' => env('APP_ENV') === 'local' 
            ? "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net http://localhost:5173 http://127.0.0.1:5173 http://[::]:5173 http://[::1]:5173"
            : "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
        'style_src' => env('APP_ENV') === 'local'
            ? "'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net http://localhost:5173 http://127.0.0.1:5173 http://[::]:5173 http://[::1]:5173"
            : "'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net https://cdn.jsdelivr.net",
        'font_src' => "'self' https://fonts.gstatic.com https://fonts.bunny.net",
        'img_src' => "'self' data: https:",
        'connect_src' => env('APP_ENV') === 'local'
            ? "'self' ws://localhost:5173 ws://127.0.0.1:5173 ws://[::]:5173 ws://[::1]:5173 http://localhost:5173 http://127.0.0.1:5173 http://[::]:5173 http://[::1]:5173"
            : "'self'",
        'frame_ancestors' => "'none'",
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'log_user_actions' => env('AUDIT_LOG_ACTIONS', true),
        'log_ip_addresses' => env('AUDIT_LOG_IPS', true),
        'log_user_agents' => env('AUDIT_LOG_USER_AGENTS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    */
    'ip_filtering' => [
        'enabled' => env('IP_FILTERING_ENABLED', false),
        'whitelist' => env('IP_WHITELIST', ''),
        'blacklist' => env('IP_BLACKLIST', ''),
    ],
];