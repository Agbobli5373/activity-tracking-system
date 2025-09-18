<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $activity = $this->route('activity');
        
        // Users can update activities they created or are assigned to
        // Admins and supervisors can update any activity
        return auth()->check() && (
            auth()->user()->canManageActivities() ||
            $activity->created_by === auth()->id() ||
            $activity->assigned_to === auth()->id()
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'name.required' => 'Activity name is required.',
            'name.max' => 'Activity name cannot exceed 255 characters.',
            'description.required' => 'Activity description is required.',
            'priority.in' => 'Priority must be low, medium, or high.',
            'assigned_to.exists' => 'The selected user does not exist.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ];
    }
}
