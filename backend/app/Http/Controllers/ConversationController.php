<?php

namespace App\Http\Controllers;

use App\Http\Requests\Conversation\StoreConversationRequest;
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

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    public function store(StoreConversationRequest $request)
    {
        $user = Auth::user();
        $otherUserId = $request->other_user_id;
        if ($user->id == $otherUserId) {
            return response()->json(['error' => 'Не удается создать диалог с самим собой'], 400);
        }

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
            return response()->json([
                'success' => true,
                'message' => 'Диалог уже существует',
                'data' => $this->formatConversation($conversation)
            ]);
        }

        $user1_id = min($user->id, $otherUserId);
        $user2_id = max($user->id, $otherUserId);

        $conversation = Conversation::create([
            'user1_id' => $user1_id,
            'user2_id' => $user2_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Диалог успешно создан',
            'data' => $this->formatConversation($conversation)
        ], 201);
    }

    public function show($id)
    {
        $conversation = Conversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Диалог не найден'
            ], 404);
        }

        $user = Auth::user();
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatConversation($conversation)
        ]);
    }

    public function destroy($id)
    {
        $conversation = Conversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Диалог не найден'
            ], 404);
        }

        $user = Auth::user();
        if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу'
            ], 403);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Диалог успешно удален'
        ]);
    }

    private function formatConversation(Conversation $conversation): array
    {
        $user = Auth::user();
        $otherUser = $conversation->otherUser($user->id);

        return [
            'id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
            ],
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ];
    }
}
