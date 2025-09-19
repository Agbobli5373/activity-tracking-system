<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,manager');
    }

    /**
     * Display audit logs.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('model_type')) {
            $query->byModelType($request->model_type);
        }

        if ($request->filled('ip_address')) {
            $query->byIpAddress($request->ip_address);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', '%' . $request->search . '%')
                  ->orWhere('url', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($userQuery) use ($request) {
                      $userQuery->where('name', 'like', '%' . $request->search . '%')
                               ->orWhere('employee_id', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(25);
        
        // Get filter options
        $users = User::select('id', 'name', 'employee_id')->orderBy('name')->get();
        $actions = AuditLog::select('action')
                          ->distinct()
                          ->orderBy('action')
                          ->pluck('action');
        $modelTypes = AuditLog::select('model_type')
                             ->whereNotNull('model_type')
                             ->distinct()
                             ->orderBy('model_type')
                             ->pluck('model_type');

        return view('audit.index', compact('auditLogs', 'users', 'actions', 'modelTypes'));
    }

    /**
     * Show detailed audit log.
     */
    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');
        
        return view('audit.show', compact('auditLog'));
    }

    /**
     * Show security logs.
     */
    public function security(Request $request): View
    {
        $days = $request->input('days', 7);
        $securityLogs = AuditService::getSecurityLogs($days);
        
        return view('audit.security', compact('securityLogs', 'days'));
    }

    /**
     * Show user activity logs.
     */
    public function userActivity(Request $request, User $user): View
    {
        $this->authorize('viewAny', AuditLog::class);
        
        $limit = $request->input('limit', 100);
        $userLogs = AuditService::getUserActivityLogs($user->id, $limit);
        
        return view('audit.user-activity', compact('user', 'userLogs', 'limit'));
    }

    /**
     * Export audit logs.
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->limit(10000)->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($auditLogs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'User',
                'Employee ID',
                'Action',
                'Model Type',
                'Model ID',
                'IP Address',
                'URL',
                'Method',
                'Changes Summary',
                'Created At'
            ]);

            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user?->name ?? 'System',
                    $log->user?->employee_id ?? '',
                    $log->action,
                    $log->model_type ?? '',
                    $log->model_id ?? '',
                    $log->ip_address,
                    $log->url,
                    $log->method,
                    $log->changes_summary ?? '',
                    $log->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
