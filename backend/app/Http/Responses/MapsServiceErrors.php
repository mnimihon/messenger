<?php
namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

trait MapsServiceErrors
{
    protected function jsonError(string $code, ?string $message = null): JsonResponse
    {
        [$status, $defaultMessage] = match ($code) {
            ServiceResult::NOT_FOUND => [404, 'Диалог не найден'],
            ServiceResult::FORBIDDEN => [403, 'У вас нет доступа к этому диалогу'],
            ServiceResult::TYPE_SELF => [400, 'Не удается создать диалог с самим собой'],
            default => throw new \InvalidArgumentException('Неизвестный код результата сервиса: '.$code),
        };

        return response()->json([
            'success' => false,
            'message' => $message ?? $defaultMessage,
        ], $status);
    }
}
