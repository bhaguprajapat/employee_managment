<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $userId = $employee->user_id;

        return [
            'employee_code' => [
                'required', 'string', 'max:20',
                Rule::unique('employees', 'employee_code')->ignore($employee->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:191',
                Rule::unique('employees', 'email')->ignore($employee->id),
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'mobile_number' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in([Employee::STATUS_ACTIVE, Employee::STATUS_INACTIVE])],
            // Optional: leave blank to keep the current password.
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Enter a valid mobile number (10 to 15 digits, optional leading +).',
            'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
        ];
    }
}
