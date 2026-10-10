<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
        return [
            'employee_code' => ['required', 'string', 'max:20', 'unique:employees,employee_code'],
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:191',
                'unique:employees,email',
                'unique:users,email',
            ],
            'mobile_number' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in([Employee::STATUS_ACTIVE, Employee::STATUS_INACTIVE])],
            // A login is created with every employee so they can mark attendance.
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
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
