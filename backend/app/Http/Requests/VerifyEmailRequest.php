<?php

namespace App\Http\Requests;

class VerifyEmailRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Пользователь с таким email не найден',
            'code.size' => 'Код должен содержать 6 цифр',
        ];
    }
}
