<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'regex:/^(?:\+63|0)9\d{9}$/'],
            'address' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => ['required', 'in:viewer,operator,vehicle_owner'],
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['mobile_number' => 'mobile number', 'terms' => 'Terms and Conditions', 'privacy' => 'Privacy Policy'];
    }
}