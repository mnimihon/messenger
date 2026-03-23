<?php

namespace App\Http\Requests\Profile;

class UpdatePasswordRequest extends BaseProfileRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }
}
