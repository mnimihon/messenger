<?php

namespace App\Http\Requests\Auth;

class LoginRequest extends BaseAuthRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}
