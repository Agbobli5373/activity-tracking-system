<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')->id;

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
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_id')->ignore($userId),
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
                'sometimes',
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
                Rule::in(['active', 'inactive', 'locked', 'pending']),
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', // At least one lowercase, uppercase, and number
            ],
            'password_confirmation' => [
                'required_with:password',
                'string',
            ],
            'force_password_change' => [
                'sometimes',
                'boolean',
            ],
            'unlock_account' => [
                'sometimes',
                'boolean',
            ],
            'reset_failed_attempts' => [
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
            'password.min' => 'The password must be at least 8 characters long.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password_confirmation.required_with' => 'Please confirm the password.',
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
            'password_confirmation' => 'password confirmation',
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

        // Convert checkboxes to boolean
        $booleanFields = ['force_password_change', 'unlock_account', 'reset_failed_attempts'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => (bool) $this->get($field)]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent users from changing their own status to inactive
            if ($this->route('user')->id === $this->user()->id) {
                if ($this->has('status') && $this->get('status') === 'inactive') {
                    $validator->errors()->add('status', 'You cannot deactivate your own account.');
                }
            }

            // Validate password policy if password is being changed
            if ($this->has('password')) {
                $this->validatePasswordPolicy($validator);
            }
        });
    }

    /**
     * Validate password against system policy.
     */
    protected function validatePasswordPolicy($validator): void
    {
        $password = $this->get('password');
        
        // Get password policy from system settings (fallback to defaults)
        $policy = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ];

        // Check minimum length
        if (strlen($password) < $policy['min_length']) {
            $validator->errors()->add('password', "Password must be at least {$policy['min_length']} characters long.");
        }

        // Check uppercase requirement
        if ($policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $validator->errors()->add('password', 'Password must contain at least one uppercase letter.');
        }

        // Check lowercase requirement
        if ($policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $validator->errors()->add('password', 'Password must contain at least one lowercase letter.');
        }

        // Check numbers requirement
        if ($policy['require_numbers'] && !preg_match('/\d/', $password)) {
            $validator->errors()->add('password', 'Password must contain at least one number.');
        }

        // Check symbols requirement
        if ($policy['require_symbols'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $validator->errors()->add('password', 'Password must contain at least one special character.');
        }
    }
}