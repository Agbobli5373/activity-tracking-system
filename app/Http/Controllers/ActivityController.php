<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Requests\UpdateActivityStatusRequest;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    protected ActivityService $activityService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ActivityService $activityService)
    {
        $this->middleware('auth');
        $this->activityService = $activityService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Activity::with(['creator', 'assignee', 'updates' => function ($query) {
            $query->latest()->limit(1);
        }]);

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('date')) {
            $query->byDate($request->date);
        }

        if ($request->filled('creator')) {
            $query->byCreator($request->creator);
        }

        if ($request->filled('assignee')) {
            $query->byAssignee($request->assignee);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $activities = $query->latest()->paginate(15);
        $users = User::select('id', 'name')->get();

        return view('activities.index', compact('activities', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $users = User::select('id', 'name')->get();
        return view('activities.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $activity = Activity::create($validated);

        // Create initial audit entry
        ActivityUpdate::createAuditEntry(
            $activity->id,
            auth()->id(),
            null,
            'pending',
            'Activity created',
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Activity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): View
    {
        $activity->load([
            'creator',
            'assignee',
            'updates' => function ($query) {
                $query->with('user')->chronological();
            }
        ]);

        return view('activities.show', compact('activity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);
        
        $users = User::select('id', 'name')->get();
        return view('activities.edit', compact('activity', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validated();
        
        $activity->update($validated);

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Activity updated successfully.');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(UpdateActivityStatusRequest $request, Activity $activity): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        
        $this->activityService->updateStatus(
            $activity,
            $validated['status'],
            $validated['remarks'],
            $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Activity status updated successfully.',
                'activity' => $activity->fresh(['creator', 'assignee']),
                'availableTransitions' => $this->activityService->getAvailableStatusTransitions($activity->fresh())
            ]);
        }

        return redirect()->route('activities.show', $activity)
            ->with('success', 'Activity status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);
        
        // Delete related updates first
        $activity->updates()->delete();
        $activity->delete();

        return redirect()->route('activities.index')
            ->with('success', 'Activity deleted successfully.');
    }

    /**
     * Get activities for API consumption.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Activity::with(['creator', 'assignee']);

        // Apply filters
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('date')) {
            $query->byDate($request->date);
        }

        if ($request->filled('creator')) {
            $query->byCreator($request->creator);
        }

        if ($request->filled('assignee')) {
            $query->byAssignee($request->assignee);
        }

        $activities = $query->latest()->paginate(15);

        return response()->json($activities);
    }

    /**
     * Get single activity for API consumption.
     */
    public function apiShow(Activity $activity): JsonResponse
    {
        $activity->load([
            'creator',
            'assignee',
            'updates' => function ($query) {
                $query->with('user')->chronological();
            }
        ]);

        return response()->json($activity);
    }
}
