<?php

namespace App\Http\Requests\Message;

class MarkAsReadRequest extends BaseMessageRequest
{
    public function rules(): array
    {
        return [
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'message_ids.required' => 'Список сообщений обязателен',
            'message_ids.array' => 'Неверный формат списка сообщений',
            'message_ids.*.exists' => 'Одно из сообщений не найдено',
        ];
    }

    public function attributes(): array
    {
        return [
            'message_ids' => 'список сообщений',
        ];
    }
}
