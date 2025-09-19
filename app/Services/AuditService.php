<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Log a user action.
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): AuditLog {
        $request = $request ?? request();
        
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'request_data' => self::sanitizeRequestData($request->all()),
            'session_id' => $request->session()->getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log authentication events.
     */
    public static function logAuth(string $action, ?int $userId = null, ?Request $request = null): AuditLog
    {
        $request = $request ?? request();
        
        // For failed login attempts, don't use user_id if user doesn't exist
        $finalUserId = $userId ?? Auth::id();
        
        // If we still don't have a valid user ID and it's a failed login, set to null
        if (!$finalUserId && str_contains($action, 'failed')) {
            $finalUserId = null;
        }
        
        return AuditLog::create([
            'user_id' => $finalUserId,
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'session_id' => $request->session()->getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log model changes with before/after values.
     */
    public static function logModelChange(
        string $action,
        $model,
        ?array $oldValues = null,
        ?Request $request = null
    ): AuditLog {
        $newValues = null;
        
        if ($model && method_exists($model, 'toArray')) {
            $newValues = $model->toArray();
        }

        return self::log(
            $action,
            get_class($model),
            $model->id ?? null,
            $oldValues,
            $newValues,
            $request
        );
    }

    /**
     * Get audit logs for a specific model.
     */
    public static function getModelAuditLogs($model, int $limit = 50)
    {
        return AuditLog::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user activity logs.
     */
    public static function getUserActivityLogs(int $userId, int $limit = 100)
    {
        return AuditLog::byUser($userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get security-related logs (failed logins, suspicious activity).
     */
    public static function getSecurityLogs(int $days = 7)
    {
        return AuditLog::whereIn('action', [
                'login_failed',
                'login_success',
                'logout',
                'password_change',
                'unauthorized_access'
            ])
            ->recent($days)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up old audit logs.
     */
    public static function cleanup(int $daysToKeep = 365): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }

    /**
     * Sanitize request data to remove sensitive information.
     */
    private static function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            '_token',
            'api_key',
            'secret',
            'token'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Execute a database transaction with audit logging.
     */
    public static function transaction(callable $callback, string $action, ?string $description = null)
    {
        try {
            $result = DB::transaction(function () use ($callback, $action, $description) {
                $result = $callback();
                
                self::log($action . '_success', null, null, null, [
                    'description' => $description,
                    'result' => 'success'
                ]);
                
                return $result;
            });
            
            return $result;
        } catch (\Exception $e) {
            // Log failure outside of transaction so it doesn't get rolled back
            self::log($action . '_failed', null, null, null, [
                'description' => $description,
                'error' => $e->getMessage(),
                'result' => 'failed'
            ]);
            
            throw $e;
        }
    }
}