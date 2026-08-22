<?php

namespace App\Http\Requests;

use App\Support\UserPasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'old_pass' => ['required', 'string', 'max:' . UserPasswordPolicy::maximum()],
            'password' => UserPasswordPolicy::rules(),
        ];
    }

    public function attributes(): array
    {
        return [
            'old_pass' => 'current password',
        ];
    }
}
