<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected ReportService $reportService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ReportService $reportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
    }

    /**
     * Display the reporting interface.
     */
    public function index(): View
    {
        $filterOptions = $this->reportService->getFilterOptions();
        
        return view('reports.index', compact('filterOptions'));
    }

    /**
     * Generate report based on criteria.
     */
    public function generate(\App\Http\Requests\GenerateReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $reportData = $this->reportService->generateActivityReport($validated);
            
            return response()->json([
                'success' => true,
                'data' => $reportData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity trends for charts.
     */
    public function trends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $groupBy = $validated['group_by'] ?? 'day';

            $trends = $this->reportService->getActivityTrends($startDate, $endDate, $groupBy);
            
            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get department statistics.
     */
    public function departmentStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $stats = $this->reportService->getDepartmentStatistics($startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get department statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report in specified format.
     */
    public function export(\App\Http\Requests\ExportReportRequest $request): Response|StreamedResponse
    {
        $validated = $request->validated();

        try {
            $reportData = $this->reportService->generateActivityReport($validated);
            
            switch ($validated['format']) {
                case 'pdf':
                    return $this->exportToPdf($reportData);
                case 'excel':
                    return $this->exportToExcel($reportData);
                case 'csv':
                    return $this->exportToCsv($reportData);
                default:
                    throw new \InvalidArgumentException('Invalid export format');
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to PDF format.
     */
    private function exportToPdf(array $reportData): Response|StreamedResponse
    {
        $filename = 'activity_report_' . date('Y-m-d_H-i-s') . '.pdf';
        
        $pdf = Pdf::loadView('reports.pdf', $reportData);
        
        return $pdf->download($filename);
    }

    /**
     * Export report to Excel format.
     */
    private function exportToExcel(array $reportData): StreamedResponse
    {
        $filename = 'activity_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ];

        $callback = function () use ($reportData) {
            $file = fopen('php://output', 'w');
            
            // Write BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write report summary
            fputcsv($file, ['Activity Report Summary']);
            fputcsv($file, ['Generated on', date('Y-m-d H:i:s')]);
            fputcsv($file, ['Period', $reportData['period']['start_date'] . ' to ' . $reportData['period']['end_date']]);
            fputcsv($file, []); // Empty row
            
            // Write statistics
            fputcsv($file, ['Statistics']);
            fputcsv($file, ['Total Activities', $reportData['statistics']['total_activities']]);
            fputcsv($file, ['Completed Activities', $reportData['statistics']['completed_activities']]);
            fputcsv($file, ['Pending Activities', $reportData['statistics']['pending_activities']]);
            fputcsv($file, ['Completion Rate', $reportData['statistics']['completion_rate'] . '%']);
            fputcsv($file, []); // Empty row
            
            // Write user statistics if available
            if (!empty($reportData['statistics']['user_statistics'])) {
                fputcsv($file, ['User Performance']);
                fputcsv($file, [
                    'User Name',
                    'Department',
                    'Total Activities',
                    'Completed Activities',
                    'Pending Activities',
                    'Completion Rate'
                ]);
                
                foreach ($reportData['statistics']['user_statistics'] as $user) {
                    fputcsv($file, [
                        $user['user_name'],
                        $user['department'] ?? 'N/A',
                        $user['total_activities'],
                        $user['completed_activities'],
                        $user['pending_activities'],
                        $user['completion_rate'] . '%'
                    ]);
                }
                fputcsv($file, []); // Empty row
            }
            
            // Write activities data
            fputcsv($file, ['Activities Detail']);
            fputcsv($file, [
                'Activity Name',
                'Description',
                'Status',
                'Priority',
                'Creator',
                'Assignee',
                'Department',
                'Created At',
                'Due Date',
                'Last Updated'
            ]);

            foreach ($reportData['activities'] as $activity) {
                fputcsv($file, [
                    $activity->name,
                    $activity->description,
                    ucfirst($activity->status),
                    ucfirst($activity->priority),
                    $activity->creator->name ?? 'N/A',
                    $activity->assignee->name ?? 'N/A',
                    $activity->creator->department ?? 'N/A',
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->due_date ? $activity->due_date->format('Y-m-d') : 'N/A',
                    $activity->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export report to CSV format.
     */
    private function exportToCsv(array $reportData): StreamedResponse
    {
        $filename = 'activity_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($reportData) {
            $file = fopen('php://output', 'w');
            
            // Write CSV headers
            fputcsv($file, [
                'Activity Name',
                'Description',
                'Status',
                'Priority',
                'Creator',
                'Assignee',
                'Department',
                'Created At',
                'Due Date',
                'Last Updated'
            ]);

            // Write activity data
            foreach ($reportData['activities'] as $activity) {
                fputcsv($file, [
                    $activity->name,
                    $activity->description,
                    ucfirst($activity->status),
                    ucfirst($activity->priority),
                    $activity->creator->name ?? 'N/A',
                    $activity->assignee->name ?? 'N/A',
                    $activity->creator->department ?? 'N/A',
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->due_date ? $activity->due_date->format('Y-m-d') : 'N/A',
                    $activity->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get report summary for dashboard widgets.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:today,week,month,quarter,year',
        ]);

        try {
            $period = $validated['period'] ?? 'month';
            
            [$startDate, $endDate] = $this->getPeriodDates($period);
            
            $criteria = [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ];
            
            $reportData = $this->reportService->generateActivityReport($criteria);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $reportData['statistics'],
                    'period' => $reportData['period'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get start and end dates for a given period.
     */
    private function getPeriodDates(string $period): array
    {
        $now = Carbon::now();
        
        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}