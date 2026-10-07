<?php

namespace App\Http\Requests;

use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $staffRules = $this->input('role') === 'staff' ? [
            'staff_position' => ['required', 'string', 'max:255'],
            'staff_department' => ['required', 'string', 'max:255'],
            'staff_skills' => ['required', 'string', 'max:5000'],
            'staff_experience' => ['required', 'string', 'max:5000'],
            'staff_reason' => ['required', 'string', 'max:5000'],
            'staff_additional_information' => ['nullable', 'string', 'max:5000'],
        ] : [];

        return array_merge([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => ['required', 'in:'.implode(',', RolePermissionMatrix::PUBLIC_REGISTRATION_ROLES)],
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
        ], $staffRules);
    }

    public function attributes(): array
    {
        return ['mobile_number' => 'mobile number', 'terms' => 'Terms and Conditions', 'privacy' => 'Privacy Policy'];
    }
}