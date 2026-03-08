<?php

namespace App\Http\Controllers;

use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index(ConversationService $conversationService)
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $conversationService->getConversations($user->id)
        ]);
    }

    public function store(StoreConversationRequest $request, ConversationService $conversationService)
    {
        $user = Auth::user();
        $otherUserID = $request->other_user_id;
        if ($user->id == $otherUserID) {
            return response()->json([
                'success' => true,
                'message' => 'Не удается создать диалог с самим собой'
            ], 400);
        }

        if ($conversationService->isConversationExists($user->id, $otherUserID)) {
            return response()->json([
                'success' => true,
                'message' => 'Диалог уже существует'
            ]);
        }

        $conversation = $conversationService->createConversation($user->id, $otherUserID);

        return response()->json([
            'success' => true,
            'message' => 'Диалог успешно создан',
            'data' => $this->formatConversation($conversation)
        ], 201);
    }

    public function show($id, ConversationService $conversationService)
    {
        $conversation = Conversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Диалог не найден'
            ], 404);
        }

        $user = Auth::user();
        if (!$user->hasAccessToConversation($id)) {
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

    public function destroy($id, ConversationService $conversationService)
    {
        $conversation = Conversation::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Диалог не найден'
            ], 404);
        }

        $user = Auth::user();
        if (!$user->hasAccessToConversation($id)) {
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
