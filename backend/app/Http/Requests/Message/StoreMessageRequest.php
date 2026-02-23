<?php

namespace App\Http\Requests\Message;

class StoreMessageRequest extends BaseMessageRequest
{
    public function rules(): array
    {
        return [
            'conversation_id' => 'required|integer|exists:conversations,id',
            'message' => 'required|string|max:2000',
        ];
    }
}
