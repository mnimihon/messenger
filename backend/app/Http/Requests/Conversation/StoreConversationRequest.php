<?php

namespace App\Http\Requests\Conversation;

class StoreConversationRequest extends BaseConversationRequest
{
    public function rules(): array
    {
        return [
            'other_user_id' => 'required|integer|exists:users,id|different:user_id',
        ];
    }
}
