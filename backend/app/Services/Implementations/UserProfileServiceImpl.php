<?php

namespace App\Services\Implementations;

use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Hash;

class UserProfileServiceImpl implements UserProfileService
{
    public function __construct(
        private readonly User $userModel,
    ) {}

    public function publicProfilePayload(int $userID): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function updateNameResult(int $userID, string $name): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);
        $user->updateName($name);

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Имя успешно изменено',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ];
    }

    public function updatePasswordResult(int $userID, string $currentPlainPassword, string $newPlainPassword): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);

        if (! Hash::check($currentPlainPassword, $user->password)) {
            return [
                'http_status' => 403,
                'json' => [
                    'success' => false,
                    'message' => 'Неверный текущий пароль',
                ],
            ];
        }

        $user->updatePassword($newPlainPassword);

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Пароль успешно изменен',
            ],
        ];
    }

    public function deleteAccountResult(int $userID, string $plainPassword): array
    {
        $user = $this->userModel->newQuery()->findOrFail($userID);

        if (! Hash::check($plainPassword, $user->password)) {
            return [
                'http_status' => 403,
                'json' => [
                    'success' => false,
                    'message' => 'Неверный пароль',
                ],
            ];
        }

        $user->tokens()->delete();
        $user->delete();

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Аккаунт успешно удален',
            ],
        ];
    }
}
