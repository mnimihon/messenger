<?php

namespace App\Http\Controllers;

use App\DTO\UserCreateDTO;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, UserService $userService)
    {
        $verificationCode = $userService->generateCode();
        $user = $userService->create(new UserCreateDTO(
            name: $request->name,
            email: $request->email,
            password: Hash::make($request->password),
            verificationCode: $verificationCode,
            verificationCodeExpiresAt: now()->addMinutes(10)
        ));
        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешна. Требуется подтверждение email.'
        ], 201);
    }


    public function verifyEmail(VerifyEmailRequest $request, UserService $userService)
    {
        $user = $userService->getByEmail($request->email);
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email уже подтвержден'
            ], 400);
        }

        if ($userService->checkVerificationCode($user, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный или истекший код подтверждения'
            ], 400);
        }

        $userService->setVerifiedEmail($user);
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => 'Email успешно подтвержден',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    public function resendCode(ResendCodeRequest $request, UserService $userService)
    {
        $user = $userService->getByEmail($request->email);
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email уже подтвержден'
            ], 400);
        }

        if ($userService->trySendCodeLater($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через минуту.',
            ], 429);
        }

        $verificationCode = $userService->generateCode();
        $userService->setVerificationCode($user, $verificationCode);

        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return response()->json([
            'success' => true,
            'message' => 'Новый код подтверждения отправлен на ваш email'
        ]);
    }

    public function login(LoginRequest $request, UserService $userService)
    {
        $user = $userService->getByEmail($request->email);
        if ($user) {
            if ($userService->isTooManyFailedLoginAttempts($user)) {
                $minutesLeft = round($user->login_locked_until->diffInMinutes(now()));
                return response()->json([
                    'success' => false,
                    'message' => "Слишком много неудачных попыток входа. Попробуйте через {$minutesLeft} минут.",
                    'locked_until' => $user->login_locked_until,
                    'attempts_left' => 0
                ], 429);
            }
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            if ($user) {
                $user->increment('login_attempts');
                if ($user->login_attempts >= UserService::LOGIN_ATTEMPTS) {
                    $userService->setCloseLogin($user);

                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много неудачных попыток входа. Доступ заблокирован на 15 минут.',
                        'locked_until' => $user->login_locked_until,
                        'attempts_left' => 0
                    ], 429);
                }

                $attemptsLeft = $userService->decrementAttempts($user, UserService::LOGIN_ATTEMPTS);
                return response()->json([
                    'success' => false,
                    'message' => "Неверный email или пароль. Осталось попыток: {$attemptsLeft}",
                    'attempts_left' => $attemptsLeft
                ], 401);
            }


            return response()->json([
                'success' => false,
                'message' => 'Неверный email или пароль'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email не подтвержден',
                'email' => $user->email
            ], 403);
        }

        $userService->setZeroLoginAttempts($user);

        $user->tokens()->delete();
        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => 'Вход выполнен успешно',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest  $request, UserService $userService)
    {
        $user = $userService->getByEmail($request->email);
        if ($userService->isCodeAlreadyBeenSent($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через ' . UserService::RESET_PASSWORD_MINUTES . ' минуту.',
                'can_resend_after' => UserService::CAN_RESEND_AFTER - round($user->reset_password_code_sent_at->diffInSeconds(now()))
            ], 429);
        }

        if ($userService->isTooManyFailedResetAttempts($user)) {
            $minutesLeft = round($user->reset_password_locked_until->diffInMinutes(now()));
            return response()->json([
                'success' => false,
                'message' => "Слишком много неудачных попыток. Попробуйте через {$minutesLeft} минут.",
                'locked_until' => $user->reset_password_locked_until
            ], 429);
        }

        $code = $userService->generateCode();
        $userService->setResetPasswordCode($user, $code);

        $user->notify(new ResetPasswordNotification($code));

        return response()->json([
            'success' => true,
            'message' => 'Код для сброса пароля отправлен на ваш email'
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, UserService $userService)
    {
        $user = $userService->getByEmail($request->email);

        if ($userService->isTooManyFailedResetAttempts($user)) {
            $minutesLeft = round($user->reset_password_locked_until->diffInMinutes(now()));
            return response()->json([
                'success' => false,
                'message' => "Слишком много неудачных попыток. Попробуйте через {$minutesLeft} минут.",
                'locked_until' => $user->reset_password_locked_until
            ], 429);
        }

        if (!$user->reset_password_code) {
            return response()->json([
                'success' => false,
                'message' => 'Код не найден. Запросите новый код.'
            ], 400);
        }

        if ($user->reset_password_code_expires_at < now()) {
            $userService->setResetPasswordCodeNull($user);

            return response()->json([
                'success' => false,
                'message' => 'Код истек. Запросите новый код.'
            ], 400);
        }

        if ($user->reset_password_code != $request->code) {
            $user->increment('reset_password_attempts');

            if ($user->reset_password_attempts >= UserService::RESET_PASSWORD_ATTEMPTS) {
                $userService->setCloseResetPassword($user);

                return response()->json([
                    'success' => false,
                    'message' => 'Слишком много неудачных попыток. Доступ заблокирован на 15 минут.',
                    'locked_until' => $user->reset_password_locked_until
                ], 429);
            }

            $attemptsLeft = $userService->decrementAttempts($user, UserService::RESET_PASSWORD_ATTEMPTS);

            return response()->json([
                'success' => false,
                'message' => "Неверный код. Осталось попыток: {$attemptsLeft}",
                'attempts_left' => $attemptsLeft
            ], 400);
        }

        $userService->setPassword($user->id, $request->password);
        $userService->setPasswordNull($user->id);

        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(User::TOKEN_LIFETIME));

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно изменен',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Успешный выход'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
        ]);
    }
}
