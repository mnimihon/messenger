<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id'
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where('conversation_id', $conversation->id)
            ->with('sender:id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'messages' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'total' => $messages->total(),
                'has_more' => $messages->hasMorePages(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        $conversation->touch();

        $message->load('sender:id,name,avatar_url');

        event(new MessageSent($message));

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

    public function markAsRead(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id',
        ]);

        $user = Auth::user();

        Message::whereIn('id', $request->message_ids)
            ->where('sender_id', '!=', $user->id)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    public function destroy(Message $message)
    {
        $user = Auth::user();
        if ($message->sender_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }
}
