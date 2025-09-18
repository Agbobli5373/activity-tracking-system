<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display the daily dashboard
     */
    public function index(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');

        $dashboardData = $this->dashboardService->getDailyDashboardData($date, [
            'status' => $status,
            'department' => $department,
        ]);

        return view('dashboard.index', compact('dashboardData', 'date', 'status', 'department'));
    }

    /**
     * Get filtered activities for AJAX requests
     */
    public function getActivities(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');

        $activities = $this->dashboardService->getFilteredActivities($date, [
            'status' => $status,
            'department' => $department,
        ]);

        return response()->json([
            'activities' => $activities,
            'summary' => $this->dashboardService->getActivitySummary($activities)
        ]);
    }

    /**
     * Display handover information
     */
    public function handover(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        
        $handoverData = $this->dashboardService->getHandoverData($date);

        return view('dashboard.handover', compact('handoverData', 'date'));
    }

    /**
     * Get real-time activity updates
     */
    public function getUpdates(Request $request)
    {
        $lastUpdate = $request->get('last_update');
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        $updates = $this->dashboardService->getRecentUpdates($date, $lastUpdate);

        return response()->json([
            'updates' => $updates,
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }
}