<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'ID диалога обязателен',
            'conversation_id.exists' => 'Диалог не найден',
            'conversation_id.integer' => 'ID диалога должен быть числом',
            'message.required' => 'Текст сообщения обязателен',
            'message.string' => 'Сообщение должно быть строкой',
            'message.max' => 'Сообщение не может быть длиннее 2000 символов',
            'message_ids.required' => 'Не выбраны сообщения для отметки',
            'message_ids.array' => 'Неверный формат списка сообщений',
            'message_ids.*.exists' => 'Одно из сообщений не найдено',
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
