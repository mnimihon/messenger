<?php

namespace App\Http\Requests\Message;

class IndexMessageRequest extends BaseMessageRequest
{
    public function rules(): array
    {
        return [
            'conversation_id' => 'required|integer|exists:conversations,id',
        ];
    }
}
