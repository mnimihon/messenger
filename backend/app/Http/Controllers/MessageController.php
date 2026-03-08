<?php

namespace App\Http\Controllers;

use App\DTO\MessageDTO;
use App\Events\MessageSent;
use App\Http\Requests\Message\IndexMessageRequest;
use App\Http\Requests\Message\MarkAsReadRequest;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagesService;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(IndexMessageRequest $request, MessagesService $messagesService)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);
        if (!$user->hasAccessToConversation($conversation->id)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        $messagesService->setIsReadAll($user->id, $conversation);

        return response()->json([
            'success' => true,
            'data' => $messagesService->getByConversation($conversation, $request->cursor)
        ]);
    }

    public function store(StoreMessageRequest $request, MessagesService $messagesService)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);
        if (!$user->hasAccessToConversation($conversation->id)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        $message = $messagesService->create(
            new MessageDTO(
                conversationID: $conversation->id,
                senderID: $user->id,
                message: $request->message,
                isRead: false,
                createdAt: new \DateTime()
            )
        );
        $message->load('sender:id,name');

        event(new MessageSent($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                ],
                'message' => $message->message,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at,
            ]
        ], 201);
    }

    public function markAsRead(MarkAsReadRequest $request)
    {
        $user = Auth::user();

        $updatedCount = Message::whereIn('id', $request->message_ids)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', 0)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user1_id', $user->id)
                    ->orWhere('user2_id', $user->id);
            })
            ->update(['is_read' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Сообщения отмечены как прочитанные',
            'data' => [
                'marked_count' => $updatedCount
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Сообщение не найдено'
            ], 404);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Вы можете удалять только свои сообщения'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено'
        ]);
    }
}
