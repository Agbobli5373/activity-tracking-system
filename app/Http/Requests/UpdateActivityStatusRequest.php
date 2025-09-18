<?php

namespace App\Http\Requests;

use App\Rules\NoMaliciousContent;
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
            'remarks' => [
                'required',
                'string',
                'min:10',
                'max:1000',
                'regex:/^[a-zA-Z0-9\s\-_.,()!?]+$/', // Allow alphanumeric, spaces, and common punctuation
                new NoMaliciousContent(),
            ],
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
            'remarks.max' => 'Remarks cannot exceed 1000 characters.',
            'remarks.regex' => 'Remarks contain invalid characters. Only letters, numbers, spaces, and common punctuation are allowed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            // Sanitize input data
            'remarks' => $this->input('remarks') ? trim(strip_tags($this->input('remarks'))) : null,
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
            'status' => 'activity status',
            'remarks' => 'status remarks',
        ];
    }
}
