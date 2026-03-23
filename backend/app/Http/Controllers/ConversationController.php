<?php

namespace App\Http\Controllers;

use App\Http\Responses\ServiceResult;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function index(ConversationService $conversationService)
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => $conversationService->listConversations($user->id),
        ]);
    }

    public function store(StoreConversationRequest $request, ConversationService $conversationService)
    {
        $user = Auth::user();
        $otherUserID = $request->other_user_id;

        $result = $conversationService->createConversation($user->id, $otherUserID);

        if ($result['type'] === ServiceResult::TYPE_SELF) {
            return $this->jsonError(ServiceResult::TYPE_SELF);
        }

        if ($result['type'] === ServiceResult::TYPE_EXISTS) {
            return response()->json([
                'success' => true,
                'message' => 'Диалог уже существует',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Диалог успешно создан',
            'data' => $conversationService->formatConversationSummary($result['conversation']->id, $user->id),
        ], 201);
    }

    public function show($id, ConversationService $conversationService)
    {
        $user = Auth::user();
        $result = $conversationService->getConversationForViewer($user->id, (int) $id);

        if (isset($result['error']) && $result['error'] === ServiceResult::NOT_FOUND) {
            return $this->jsonError(ServiceResult::NOT_FOUND);
        }

        if (isset($result['error']) && $result['error'] === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    public function otherUserPhotos($id, ConversationService $conversationService)
    {
        $user = Auth::user();
        $result = $conversationService->getOtherUserPhotos($user->id, (int) $id);

        if (isset($result['error']) && $result['error'] === ServiceResult::NOT_FOUND) {
            return $this->jsonError(ServiceResult::NOT_FOUND);
        }

        if (isset($result['error']) && $result['error'] === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    public function destroy($id, ConversationService $conversationService)
    {
        $user = Auth::user();
        $error = $conversationService->deleteConversation($user->id, (int) $id);

        if ($error === ServiceResult::NOT_FOUND) {
            return $this->jsonError(ServiceResult::NOT_FOUND);
        }

        if ($error === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'message' => 'Диалог успешно удален',
        ]);
    }
}
