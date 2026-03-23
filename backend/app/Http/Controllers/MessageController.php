<?php

namespace App\Http\Controllers;

use App\Http\Responses\ServiceResult;
use App\Http\Requests\Message\IndexMessageRequest;
use App\Http\Requests\Message\MarkAsReadRequest;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Services\MessagesService;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(IndexMessageRequest $request, MessagesService $messagesService)
    {
        $user = Auth::user();

        $result = $messagesService->loadMessagesPage(
            $user->id,
            (int) $request->conversation_id,
            (int) $request->input('cursor', 0)
        );

        if (isset($result['error'])) {
            return $this->jsonError(ServiceResult::FORBIDDEN);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    public function store(StoreMessageRequest $request, MessagesService $messagesService)
    {
        $user = Auth::user();

        $result = $messagesService->sendMessage(
            $user->id,
            (int) $request->conversation_id,
            $request->message
        );

        if (isset($result['error'])) {
            return $this->jsonError(ServiceResult::FORBIDDEN);
        }

        $message = $result['message'];

        return response()->json([
            'message' => $messagesService->messageToArray($message),
        ], 201);
    }

    public function markAsRead(MarkAsReadRequest $request, MessagesService $messagesService)
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'message' => 'Сообщения отмечены как прочитанные',
            'data' => [
                'marked_count' => $messagesService->markAsRead($user->id, $request->message_ids),
            ],
        ]);
    }

    public function destroy($message, MessagesService $messagesService)
    {
        $user = Auth::user();

        $error = $messagesService->deleteMessage($user->id, (int) $message);

        if ($error === ServiceResult::NOT_FOUND) {
            return $this->jsonError(ServiceResult::NOT_FOUND, 'Сообщение не найдено');
        }

        if ($error === ServiceResult::FORBIDDEN) {
            return $this->jsonError(ServiceResult::FORBIDDEN, 'Вы можете удалять только свои сообщения');
        }

        return response()->json([
            'success' => true,
            'message' => 'Сообщение удалено',
        ]);
    }
}
