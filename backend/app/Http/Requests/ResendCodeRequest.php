<?php

namespace App\Http\Requests;

class ResendCodeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Пользователь с таким email не найден',
        ];
    }
}
