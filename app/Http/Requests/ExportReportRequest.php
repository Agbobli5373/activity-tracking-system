<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'format' => 'required|in:pdf,excel,csv',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'status' => 'nullable|in:pending,done',
            'creator_id' => 'nullable|exists:users,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high',
            'department' => 'nullable|string|max:100',
            'filename' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_\-\s]+$/',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'format.required' => 'Export format is required.',
            'format.in' => 'Export format must be PDF, Excel, or CSV.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'end_date.before_or_equal' => 'End date cannot be in the future.',
            'status.in' => 'Status must be either pending or done.',
            'creator_id.exists' => 'Selected creator does not exist.',
            'assignee_id.exists' => 'Selected assignee does not exist.',
            'priority.in' => 'Priority must be low, medium, or high.',
            'department.max' => 'Department name cannot exceed 100 characters.',
            'filename.max' => 'Filename cannot exceed 255 characters.',
            'filename.regex' => 'Filename can only contain letters, numbers, spaces, hyphens, and underscores.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'creator_id' => 'creator',
            'assignee_id' => 'assignee',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up empty values
        $this->merge(array_filter($this->all(), function ($value) {
            return $value !== '' && $value !== null;
        }));

        // Generate default filename if not provided
        if (!$this->has('filename') || empty($this->input('filename'))) {
            $format = $this->input('format', 'csv');
            $startDate = $this->input('start_date', date('Y-m-d'));
            $endDate = $this->input('end_date', date('Y-m-d'));
            
            $filename = "activity_report_{$startDate}_to_{$endDate}";
            $this->merge(['filename' => $filename]);
        }
    }
}