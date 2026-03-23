<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [
            'other_user_id.required' => 'ID собеседника обязателен',
            'other_user_id.exists' => 'Выбранный пользователь не существует',
            'other_user_id.integer' => 'ID собеседника должен быть числом',
            'other_user_id.different' => 'Нельзя создать чат с самим собой',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422)
        );
    }
}
