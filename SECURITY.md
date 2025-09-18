# Security Features

This document outlines the comprehensive security measures implemented in the Activity Tracking System.

## Overview

The Activity Tracking System implements multiple layers of security to protect against common web vulnerabilities and ensure data integrity.

## Security Features Implemented

### 1. Input Validation and Sanitization

#### Form Request Validation

-   **Custom Form Requests**: All user inputs are validated using Laravel Form Request classes
-   **NoMaliciousContent Rule**: Custom validation rule that detects and blocks:
    -   XSS attempts (script tags, javascript:, event handlers)
    -   SQL injection patterns (UNION, SELECT, DROP, etc.)
    -   Path traversal attempts (../)
-   **Input Sanitization**: Automatic sanitization of all inputs through `SanitizeInputMiddleware`
-   **Length Limits**: Enforced maximum input lengths to prevent buffer overflow attacks

#### Validation Rules

```php
// Example validation rules applied
'name' => [
    'required',
    'string',
    'max:255',
    'min:3',
    'regex:/^[a-zA-Z0-9\s\-_.,()]+$/',
    new NoMaliciousContent(),
],
```

### 2. CSRF Protection

-   **Laravel CSRF**: Built-in CSRF protection enabled for all web routes
-   **Token Validation**: All forms include `@csrf` directive
-   **API Protection**: Sanctum tokens for API authentication

### 3. XSS Protection

-   **Blade Escaping**: All output automatically escaped using `{{ }}` syntax
-   **Content Security Policy**: Strict CSP headers to prevent script injection
-   **Input Filtering**: Malicious content detection and blocking

### 4. Security Headers

The `SecurityHeadersMiddleware` adds the following security headers:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload (HTTPS only)
Content-Security-Policy: [Configurable CSP rules]
```

### 5. Rate Limiting

#### Authentication Rate Limiting

-   **Login Attempts**: 5 attempts per 15 minutes per IP
-   **Automatic Lockout**: Temporary IP blocking after exceeded attempts

#### API Rate Limiting

-   **Report Generation**: 10 reports per hour per user
-   **Export Functions**: 5 exports per hour per user
-   **General API**: 60 requests per minute per user

### 6. IP Filtering (Optional)

-   **Whitelist/Blacklist**: Configurable IP filtering
-   **CIDR Support**: Supports subnet notation (e.g., 192.168.1.0/24)
-   **Automatic Blocking**: Immediate blocking of blacklisted IPs

### 7. Audit Logging

#### Security Event Logging

All security-related events are logged including:

-   Failed login attempts
-   XSS/SQL injection attempts
-   Path traversal attempts
-   Rate limit violations
-   IP filtering violations

#### Audit Trail

-   **User Actions**: All activity updates tracked with user details
-   **IP Addresses**: Source IP logged for all actions
-   **User Agents**: Browser/device information captured
-   **Timestamps**: Precise timing of all events

### 8. Session Security

-   **Secure Cookies**: HTTPOnly and Secure flags enabled
-   **Session Timeout**: Configurable timeout (default: 30 minutes)
-   **Session Regeneration**: New session ID on login
-   **SameSite Protection**: CSRF protection via SameSite cookies

### 9. Password Security

-   **Minimum Length**: 8 characters minimum
-   **Complexity Requirements**:
    -   Uppercase letters required
    -   Lowercase letters required
    -   Numbers required
    -   Special characters required
-   **Password Aging**: Configurable maximum password age

### 10. File Upload Security (If Implemented)

-   **Extension Validation**: Only allowed file types accepted
-   **MIME Type Checking**: Server-side MIME type validation
-   **File Size Limits**: Configurable maximum file sizes
-   **Double Extension Protection**: Prevents .php.txt attacks

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Session Security
SESSION_TIMEOUT=1800
SESSION_WARNING_TIME=300
SESSION_SECURE_COOKIE=true

# Input Validation
MAX_INPUT_LENGTH=2000
MAX_FILENAME_LENGTH=255
MAX_FILE_SIZE=10240

# Rate Limiting
LOGIN_RATE_LIMIT=5
LOGIN_DECAY_MINUTES=15
API_RATE_LIMIT=60
REPORT_RATE_LIMIT=10

# Audit Logging
AUDIT_LOG_ACTIONS=true
AUDIT_LOG_IPS=true
AUDIT_LOG_USER_AGENTS=true
AUDIT_RETENTION_DAYS=365

# Password Policy
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SYMBOLS=true
PASSWORD_MAX_AGE_DAYS=90

# IP Filtering (Optional)
IP_FILTERING_ENABLED=false
IP_WHITELIST=192.168.1.0/24,10.0.0.1
IP_BLACKLIST=
```

### Security Configuration File

The `config/security.php` file contains all security-related settings and can be customized as needed.

## Security Testing

### Automated Tests

Run the security test suite:

```bash
php artisan test --filter SecurityTest
```

### Manual Testing

1. **XSS Testing**: Try submitting `<script>alert('XSS')</script>` in forms
2. **SQL Injection**: Try submitting `'; DROP TABLE users; --` in inputs
3. **CSRF Testing**: Submit forms without CSRF tokens
4. **Rate Limiting**: Make multiple rapid requests to test limits

## Security Monitoring

### Log Files

Security events are logged to:

-   `storage/logs/laravel.log` - General application logs
-   Database audit trail in `activity_updates` table

### Monitoring Recommendations

1. **Regular Log Review**: Monitor logs for security violations
2. **Failed Login Monitoring**: Watch for brute force attempts
3. **Unusual Activity**: Monitor for suspicious user behavior
4. **Performance Impact**: Monitor rate limiting effectiveness

## Incident Response

### Security Violation Detection

When a security violation is detected:

1. **Immediate Blocking**: Request is blocked
2. **Logging**: Event is logged with full context
3. **User Notification**: Security error page displayed
4. **Admin Alert**: Consider implementing admin notifications

### Response Procedures

1. **Investigate Logs**: Review security logs for patterns
2. **IP Blocking**: Consider blocking repeat offenders
3. **User Education**: Inform users about security best practices
4. **System Updates**: Keep security measures updated

## Best Practices

### For Developers

1. **Always Validate Input**: Use form requests for all user input
2. **Escape Output**: Use Blade's `{{ }}` syntax for output
3. **Use HTTPS**: Always use HTTPS in production
4. **Regular Updates**: Keep Laravel and dependencies updated
5. **Security Reviews**: Regular code security reviews

### For Administrators

1. **Monitor Logs**: Regular review of security logs
2. **Update Configurations**: Keep security settings current
3. **Backup Strategy**: Regular secure backups
4. **Access Control**: Implement proper user role management
5. **Network Security**: Use firewalls and network security

## Compliance

This security implementation helps meet common compliance requirements:

-   **OWASP Top 10**: Protection against common web vulnerabilities
-   **Data Protection**: Audit trails and access logging
-   **Input Validation**: Comprehensive input sanitization
-   **Session Management**: Secure session handling

## Updates and Maintenance

-   **Regular Security Audits**: Quarterly security reviews recommended
-   **Dependency Updates**: Keep all packages updated
-   **Configuration Reviews**: Annual review of security settings
-   **Penetration Testing**: Consider annual penetration testing

For questions or security concerns, please contact the development team.
