<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Requests\UpdateActivityStatusRequest;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

        // Apply authorization filtering based on user role
        $user = auth()->user();
        if ($user->isAdmin()) {
            // Admins can see all activities - no additional filtering needed
        } elseif ($user->isSupervisor()) {
            // Supervisors can see activities from their department
            $query->whereHas('creator', function ($q) use ($user) {
                $q->where('department', $user->department);
            });
        } else {
            // Members can only see activities they created or are assigned to
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        }

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

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to_me')) {
            $query->where('assigned_to', auth()->id());
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
        return AuditService::transaction(function () use ($request) {
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

            // Log the activity creation
            AuditService::logModelChange('activity_created', $activity, null, $request);

            return redirect()->route('activities.show', $activity)
                ->with('success', 'Activity created successfully.');
        }, 'activity_creation', 'Creating new activity: ' . $request->name);
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);
        
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
        $this->authorize('update', $activity);
        
        return AuditService::transaction(function () use ($request, $activity) {
            $oldValues = $activity->toArray();
            $validated = $request->validated();
            
            $activity->update($validated);

            // Log the activity update
            AuditService::logModelChange('activity_updated', $activity, $oldValues, $request);

            return redirect()->route('activities.show', $activity)
                ->with('success', 'Activity updated successfully.');
        }, 'activity_update', 'Updating activity: ' . $activity->name);
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(UpdateActivityStatusRequest $request, Activity $activity): JsonResponse|RedirectResponse
    {
        $this->authorize('updateStatus', $activity);
        
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
        
        return AuditService::transaction(function () use ($activity) {
            $oldValues = $activity->toArray();
            
            // Delete related updates first
            $activity->updates()->delete();
            $activity->delete();

            // Log the activity deletion
            AuditService::logModelChange('activity_deleted', $activity, $oldValues);

            return redirect()->route('activities.index')
                ->with('success', 'Activity deleted successfully.');
        }, 'activity_deletion', 'Deleting activity: ' . $activity->name);
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
        $this->authorize('view', $activity);
        
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
