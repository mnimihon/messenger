<?php

namespace App\Http\Requests\Profile;

class DeleteAccountRequest extends BaseProfileRequest
{
    public function rules(): array
    {
        return [
            'password' => 'required|string',
        ];
    }
}
