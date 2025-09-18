<?php

namespace App\Http\Requests;

use App\Rules\NoMaliciousContent;
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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                'regex:/^[a-zA-Z0-9\s\-_.,()]+$/', // Allow alphanumeric, spaces, and common punctuation
                new NoMaliciousContent(),
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:2000',
                new NoMaliciousContent(),
            ],
            'priority' => 'nullable|in:low,medium,high',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today|before:' . now()->addYears(2)->format('Y-m-d'),
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
            'name.min' => 'Activity name must be at least 3 characters long.',
            'name.max' => 'Activity name cannot exceed 255 characters.',
            'name.regex' => 'Activity name contains invalid characters. Only letters, numbers, spaces, and common punctuation are allowed.',
            'description.required' => 'Activity description is required.',
            'description.min' => 'Activity description must be at least 10 characters long.',
            'description.max' => 'Activity description cannot exceed 2000 characters.',
            'priority.in' => 'Priority must be low, medium, or high.',
            'assigned_to.exists' => 'The selected user does not exist.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
            'due_date.before' => 'Due date cannot be more than 2 years in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            // Sanitize input data
            'name' => $this->input('name') ? trim(strip_tags($this->input('name'))) : null,
            'description' => $this->input('description') ? trim(strip_tags($this->input('description'))) : null,
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'name' => 'activity name',
            'description' => 'activity description',
            'assigned_to' => 'assignee',
            'due_date' => 'due date',
        ];
    }
}
