<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $conversations = Conversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1', 'user2', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($conversation) use ($user) {
                $otherUser = $conversation->otherUser($user->id);
                $lastMessage = $conversation->messages->first();

                return [
                    'id' => $conversation->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'avatar_url' => $otherUser->avatar_url,
                    ],
                    'last_message' => $lastMessage ? [
                        'message' => $lastMessage->message,
                        'created_at' => $lastMessage->created_at,
                        'is_read' => $lastMessage->is_read,
                    ] : null,
                    'unread_count' => $conversation->messages()
                        ->where('sender_id', '!=', $user->id)
                        ->where('is_read', false)
                        ->count(),
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return response()->json($conversations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'other_user_id' => 'required|exists:users,id'
        ]);

        $user = Auth::user();
        $otherUserId = $request->other_user_id;
        if ($user->id == $otherUserId) {
            return response()->json(['error' => 'Cannot create conversation with yourself'], 400);
        }

        // Ищем существующий диалог
        $conversation = Conversation::where(function($query) use ($user, $otherUserId) {
            $query->where('user1_id', $user->id)
                ->where('user2_id', $otherUserId);
        })
            ->orWhere(function($query) use ($user, $otherUserId) {
                $query->where('user1_id', $otherUserId)
                    ->where('user2_id', $user->id);
            })
            ->first();

        if ($conversation) {
            return $this->show($conversation);
        }

        $user1_id = min($user->id, $otherUserId);
        $user2_id = max($user->id, $otherUserId);

        $conversation = Conversation::create([
            'user1_id' => $user1_id,
            'user2_id' => $user2_id,
        ]);

        return $this->show($conversation);
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::user();
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $otherUser = $conversation->otherUser($user->id);

        return response()->json([
            'id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'avatar_url' => $otherUser->avatar_url,
            ],
            'created_at' => $conversation->created_at,
        ]);
    }

    public function destroy(Conversation $conversation)
    {
        $user = Auth::user();

        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted']);
    }
}
