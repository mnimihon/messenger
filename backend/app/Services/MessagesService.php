<?php
namespace App\Services;

use App\DTO\MessageDTO;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface MessagesService
{
    public function getByConversation(Conversation $conversation, int $cursor): array;
}
