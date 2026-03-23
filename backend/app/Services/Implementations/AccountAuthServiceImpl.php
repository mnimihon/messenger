<?php

namespace App\Services\Implementations;

use App\DTO\UserCreateDTO;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\ResetPasswordNotification;
use App\Repositories\UserRepository;
use App\Services\AccountAuthService;
use Illuminate\Support\Facades\Hash;

class AccountAuthServiceImpl implements AccountAuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function register(string $name, string $email, string $plainPassword): array
    {
        $verificationCode = $this->generateNumericAuthCode();
        $user = $this->userRepository->create(new UserCreateDTO(
            name: $name,
            email: $email,
            password: Hash::make($plainPassword),
            verificationCode: $verificationCode,
            verificationCodeExpiresAt: now()->addSeconds(User::VERIFICATION_CODE_EXPIRES_AT),
            verificationCodeSentExpiresAt: now()->addSeconds(User::CAN_RESEND_AFTER)
        ));
        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return [
            'http_status' => 201,
            'json' => [
                'success' => true,
                'message' => 'Регистрация успешна. Требуется подтверждение email.',
            ],
        ];
    }

    public function verificationCooldown(string $email): array
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user->hasVerifiedEmail()) {
            return ['http_status' => 200, 'json' => ['can_resend_after_seconds' => 0]];
        }
        if (! $user->verification_code_sent_expires_at) {
            return ['http_status' => 200, 'json' => ['can_resend_after_seconds' => 0]];
        }

        return [
            'http_status' => 200,
            'json' => ['can_resend_after_seconds' => $this->secondsUntilVerificationResendAllowed($user)],
        ];
    }

    public function verifyEmail(string $email, int|string $code): array
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user->hasVerifiedEmail()) {
            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'message' => 'Email уже подтвержден',
                ],
            ];
        }

        if ($user->checkUnVerificationCode((int) $code)) {
            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'verification_code' => $code,
                    'message' => 'Неверный или истекший код подтверждения',
                ],
            ];
        }

        $user->setVerifiedEmail();
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Email успешно подтвержден',
                'access_token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ];
    }

    public function resendVerificationCode(string $email): array
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user->hasVerifiedEmail()) {
            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'message' => 'Email уже подтвержден',
                ],
            ];
        }

        if ($user->isNotAllowResendCode()) {
            return [
                'http_status' => 429,
                'json' => [
                    'success' => false,
                    'message' => 'Код уже был отправлен. Попробуйте через минуту.',
                ],
            ];
        }

        $verificationCode = $this->generateNumericAuthCode();
        $user->setVerificationCode($verificationCode);
        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Новый код подтверждения отправлен на ваш email',
            ],
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user) {
            if ($user->isTooManyFailedLoginAttempts()) {
                $minutesLeft = abs(ceil($user->login_locked_until->diffInMinutes(now())));

                return [
                    'http_status' => 429,
                    'json' => [
                        'success' => false,
                        'message' => "Слишком много неудачных попыток входа. Попробуйте через {$minutesLeft} минут.",
                        'locked_until' => $user->login_locked_until,
                        'attempts_left' => 0,
                    ],
                ];
            }
        }

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $user->increment('login_attempts');
                if ($user->isTooManyFailedLoginAttemptsAccessBlocked()) {
                    $user->lockLogin();

                    return [
                        'http_status' => 429,
                        'json' => [
                            'success' => false,
                            'message' => 'Слишком много неудачных попыток входа. Доступ заблокирован на 15 минут.',
                            'locked_until' => $user->login_locked_until,
                            'attempts_left' => 0,
                        ],
                    ];
                }

                $attemptsLeft = $this->attemptsRemaining($user->login_attempts, User::LOGIN_ATTEMPTS);

                return [
                    'http_status' => 401,
                    'json' => [
                        'success' => false,
                        'message' => "Неверный email или пароль. Осталось попыток: {$attemptsLeft}",
                        'attempts_left' => $attemptsLeft,
                    ],
                ];
            }

            return [
                'http_status' => 401,
                'json' => [
                    'success' => false,
                    'message' => 'Неверный email или пароль',
                ],
            ];
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                'http_status' => 403,
                'json' => [
                    'success' => false,
                    'message' => 'Email не подтвержден',
                    'email' => $user->email,
                ],
            ];
        }

        $user->resetLoginAttempts();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Вход выполнен успешно',
                'access_token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ];
    }

    public function forgotPassword(string $email): array
    {
        $user = $this->userRepository->getByEmail($email);
        if ($user->isCodeAlreadyBeenSent()) {
            return [
                'http_status' => 429,
                'json' => [
                    'success' => false,
                    'message' => 'Код уже был отправлен. Попробуйте через '.User::RESET_PASSWORD_MINUTES.' минуту.',
                    'can_resend_after' => User::CAN_RESEND_AFTER - ceil($user->reset_password_code_sent_at->diffInSeconds(now())),
                ],
            ];
        }

        if ($user->isTooManyFailedResetAttempts()) {
            $minutesLeft = abs(ceil($user->reset_password_locked_until->diffInMinutes(now())));

            return [
                'http_status' => 429,
                'json' => [
                    'success' => false,
                    'message' => "Слишком много неудачных попыток. Попробуйте через {$minutesLeft} минут.",
                    'locked_until' => $user->reset_password_locked_until,
                ],
            ];
        }

        $code = $this->generateNumericAuthCode();
        $user->setResetPasswordCode($code);
        $user->notify(new ResetPasswordNotification($code));

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Код для сброса пароля отправлен на ваш email',
            ],
        ];
    }

    public function resetPassword(string $email, int|string $code, string $newPlainPassword): array
    {
        $user = $this->userRepository->getByEmail($email);

        if ($user->isTooManyFailedResetAttempts()) {
            $minutesLeft = abs(ceil($user->reset_password_locked_until->diffInMinutes(now())));

            return [
                'http_status' => 429,
                'json' => [
                    'success' => false,
                    'message' => "Слишком много неудачных попыток. Попробуйте через {$minutesLeft} минут.",
                    'locked_until' => $user->reset_password_locked_until,
                ],
            ];
        }

        if (!$user->reset_password_code) {
            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'message' => 'Код не найден. Запросите новый код.',
                ],
            ];
        }

        if ($user->isCodeExpired()) {
            $user->unlockResetPassword();

            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'message' => 'Код истек. Запросите новый код.',
                ],
            ];
        }

        if (!$user->isCodeEqual((int) $code)) {
            $user->increment('reset_password_attempts');

            if ($user->isTooManyFailedResetAttemptsAccessBlocked()) {
                $user->lockResetPassword();

                return [
                    'http_status' => 429,
                    'json' => [
                        'success' => false,
                        'message' => 'Слишком много неудачных попыток. Доступ заблокирован на 15 минут.',
                        'locked_until' => $user->reset_password_locked_until,
                    ],
                ];
            }

            $attemptsLeft = $this->attemptsRemaining($user->reset_password_attempts, User::RESET_PASSWORD_ATTEMPTS);

            return [
                'http_status' => 400,
                'json' => [
                    'success' => false,
                    'message' => "Неверный код. Осталось попыток: {$attemptsLeft}",
                    'attempts_left' => $attemptsLeft,
                ],
            ];
        }

        $user->updatePassword($newPlainPassword);
        $user->unlockResetPassword();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token', ['*'], now()->addDays(User::TOKEN_LIFETIME));

        return [
            'http_status' => 200,
            'json' => [
                'success' => true,
                'message' => 'Пароль успешно изменен',
                'access_token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ];
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function authenticatedUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
        ];
    }

    private function generateNumericAuthCode(): int
    {
        return random_int(100000, 999999);
    }

    private function attemptsRemaining(int $failedAttempts, int $maxAttempts): int
    {
        return $maxAttempts - $failedAttempts;
    }

    private function secondsUntilVerificationResendAllowed(User $user): int
    {
        $expiresAt = $user->verification_code_sent_expires_at;
        if (!$expiresAt) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($expiresAt));
    }
}
