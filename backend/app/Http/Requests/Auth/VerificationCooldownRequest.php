<?php

namespace App\Http\Requests\Auth;

class VerificationCooldownRequest extends BaseAuthRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }
}
