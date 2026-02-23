<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\Message\IndexMessageRequest;
use App\Http\Requests\Message\MarkAsReadRequest;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(IndexMessageRequest $request)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where('conversation_id', $conversation->id)
            ->with('sender:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->limit(50)->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function store(StoreMessageRequest $request)
    {
        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        $conversation->touch();

        $message->load('sender:id,name,avatar_url');

        //event(new MessageSent($message));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar_url' => $message->sender->avatar_url,
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
