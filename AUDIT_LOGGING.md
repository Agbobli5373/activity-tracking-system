# Audit Logging System

## Overview

The Activity Tracking System includes a comprehensive audit logging system that tracks all user actions, system events, and data changes for security, compliance, and monitoring purposes.

## Features

### 1. Comprehensive Logging

-   **Authentication Events**: Login attempts (success/failure), logout events
-   **Activity Management**: Creation, updates, status changes, deletions
-   **Data Changes**: Before/after values for all model changes
-   **Request Metadata**: IP address, user agent, URL, HTTP method, session ID
-   **Security Events**: Unauthorized access attempts, suspicious activities

### 2. Automatic Audit Trail

-   All user actions are automatically logged via middleware
-   Database transactions include audit logging
-   Model changes are tracked with old/new values
-   Failed operations are logged for security monitoring

### 3. Data Security

-   Sensitive data (passwords, tokens) is automatically sanitized
-   Request data is filtered to remove confidential information
-   Audit logs are stored securely with proper access controls

## Components

### AuditService

Central service for logging audit events:

```php
// Log a general action
AuditService::log('user_action', $modelType, $modelId, $oldValues, $newValues);

// Log authentication events
AuditService::logAuth('login_success', $userId);

// Log model changes
AuditService::logModelChange('activity_updated', $activity, $oldValues);

// Execute with transaction logging
AuditService::transaction(function() {
    // Your code here
}, 'operation_name', 'Description');
```

### AuditLog Model

Eloquent model for audit log entries with:

-   User relationships
-   Query scopes for filtering
-   Human-readable action descriptions
-   Changes summary generation

### AuditMiddleware

Automatically logs HTTP requests for:

-   All POST, PUT, PATCH, DELETE requests
-   Specific GET routes (dashboard, activities, reports)
-   Excludes assets and health check endpoints

### AuditLogController

Admin interface for viewing audit logs with:

-   Advanced filtering options
-   Detailed log views
-   Security log monitoring
-   CSV export functionality

## Database Schema

The `audit_logs` table includes:

-   `user_id`: Who performed the action
-   `action`: What action was performed
-   `model_type`/`model_id`: Which record was affected
-   `old_values`/`new_values`: Data changes (JSON)
-   `ip_address`: Client IP address
-   `user_agent`: Browser/client information
-   `url`/`method`: Request details
-   `request_data`: Sanitized request parameters
-   `session_id`: Session identifier
-   `created_at`: When the action occurred

## Access Control

Audit logs are restricted to:

-   **Administrators**: Full access to all audit logs
-   **Managers**: Access to department-related logs
-   **Regular Users**: No direct access to audit logs

## Maintenance

### Automatic Cleanup

-   Old audit logs are automatically cleaned up via scheduled command
-   Default retention: 365 days
-   Configurable via `audit:cleanup` command

### Manual Commands

```bash
# Clean up logs older than 365 days
php artisan audit:cleanup

# Clean up logs older than 180 days
php artisan audit:cleanup --days=180
```

## Security Features

### Data Sanitization

Automatically removes sensitive fields:

-   `password`, `password_confirmation`
-   `_token`, `api_key`, `secret`
-   Any field containing "token" or "key"

### Access Logging

All access to audit logs is itself logged, creating a complete audit trail.

### IP Tracking

All actions include IP address tracking for security monitoring and forensic analysis.

## Monitoring and Alerts

### Security Events

The system tracks and can alert on:

-   Multiple failed login attempts
-   Unauthorized access attempts
-   Unusual activity patterns
-   Data modification outside business hours

### Performance Impact

-   Audit logging is designed for minimal performance impact
-   Uses database transactions for consistency
-   Includes proper indexing for fast queries
-   Background processing for non-critical logs

## Compliance

This audit logging system helps meet compliance requirements for:

-   **SOX**: Financial data change tracking
-   **GDPR**: Data access and modification logging
-   **HIPAA**: Healthcare data audit trails
-   **ISO 27001**: Information security management

## Usage Examples

### View Recent Security Events

```php
$securityLogs = AuditService::getSecurityLogs(7); // Last 7 days
```

### Get User Activity History

```php
$userLogs = AuditService::getUserActivityLogs($userId, 100);
```

### Track Model Changes

```php
$auditTrail = AuditService::getModelAuditLogs($activity);
```

### Export Audit Data

Access `/audit/export` with appropriate filters to download CSV reports.

## Best Practices

1. **Regular Monitoring**: Review security logs weekly
2. **Retention Policy**: Adjust cleanup schedule based on compliance needs
3. **Access Control**: Limit audit log access to authorized personnel only
4. **Backup Strategy**: Include audit logs in backup procedures
5. **Performance Monitoring**: Monitor audit log table size and query performance

## Troubleshooting

### High Volume Logging

If audit logs grow too quickly:

-   Adjust cleanup retention period
-   Consider archiving old logs
-   Review logged actions for optimization

### Performance Issues

If audit logging impacts performance:

-   Check database indexes
-   Consider async logging for non-critical events
-   Review middleware configuration

### Missing Logs

If expected logs are missing:

-   Verify middleware is properly registered
-   Check user permissions
-   Review excluded routes configuration
