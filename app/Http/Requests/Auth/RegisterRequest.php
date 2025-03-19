<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRules;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', PasswordRules::min(8)->numbers()->letters()],
        ];
    }
}
