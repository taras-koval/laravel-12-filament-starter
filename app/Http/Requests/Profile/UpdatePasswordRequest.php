<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Password as PasswordRules;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => filled($this->user()->password) ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', 'confirmed', PasswordRules::min(8)->numbers()->letters()],
        ];
    }
}
