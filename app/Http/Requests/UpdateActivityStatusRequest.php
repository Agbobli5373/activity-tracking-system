<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $activity = $this->route('activity');
        
        // Users can update status of activities they created or are assigned to
        // Admins and supervisors can update any activity status
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
            'status' => 'required|in:pending,done',
            'remarks' => 'required|string|min:10',
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
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either pending or done.',
            'remarks.required' => 'Remarks are required when updating status.',
            'remarks.min' => 'Remarks must be at least 10 characters long.',
        ];
    }
}
