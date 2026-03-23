<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Profile\BaseProfileRequest;

class UpdateNameRequest extends BaseProfileRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
