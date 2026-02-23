<?php

namespace App\Http\Requests\Auth;

class ResetPasswordRequest extends BaseAuthRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
