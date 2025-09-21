<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\'\.]+$/', // Only letters, spaces, hyphens, apostrophes, and dots
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:users,employee_id',
                'regex:/^[a-zA-Z0-9\-_]+$/', // Alphanumeric with hyphens and underscores
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/', // Phone number format
            ],
            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'role' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Check if it's a valid Spatie role or legacy role
                    $spatieRoles = \Spatie\Permission\Models\Role::pluck('name')->toArray();
                    $legacyRoles = ['admin', 'supervisor', 'member'];
                    
                    if (!in_array($value, array_merge($spatieRoles, $legacyRoles))) {
                        $fail('The selected role is invalid.');
                    }
                },
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'pending']),
            ],
            'send_welcome_email' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The full name is required.',
            'name.regex' => 'The name may only contain letters, spaces, hyphens, apostrophes, and dots.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'A user with this email address already exists.',
            'employee_id.unique' => 'A user with this employee ID already exists.',
            'employee_id.regex' => 'The employee ID may only contain letters, numbers, hyphens, and underscores.',
            'phone_number.regex' => 'Please enter a valid phone number.',
            'department_id.exists' => 'The selected department does not exist.',
            'role.required' => 'Please select a role for the user.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'employee_id' => 'employee ID',
            'phone_number' => 'phone number',
            'department_id' => 'department',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        $this->merge([
            'name' => trim($this->name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
            'employee_id' => trim($this->employee_id ?? ''),
            'phone_number' => trim($this->phone_number ?? ''),
            'department' => trim($this->department ?? ''),
        ]);

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Set default for send_welcome_email if not provided
        if (!$this->has('send_welcome_email')) {
            $this->merge(['send_welcome_email' => true]);
        }
    }
}