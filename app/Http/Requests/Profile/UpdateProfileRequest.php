<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['nullable', 'phone:AUTO', Rule::unique('users', 'phone')->ignore(auth()->id())],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.phone' => __('The phone field contains an invalid number.'),
            'phone.unique' => __('This mobile number is already taken.'),
        ];
    }
}
