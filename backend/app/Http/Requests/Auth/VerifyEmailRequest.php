<?php

namespace App\Http\Requests\Auth;

class VerifyEmailRequest extends BaseAuthRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ];
    }
}
