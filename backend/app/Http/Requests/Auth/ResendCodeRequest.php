<?php

namespace App\Http\Requests\Auth;

class ResendCodeRequest extends BaseAuthRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }
}
