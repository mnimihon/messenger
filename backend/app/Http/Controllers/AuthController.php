<?php

namespace App\Http\Controllers;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendCodeRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $verificationCode = $this->generateVerificationCode();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar_url' => $this->generateAvatar($request->name),
            'verification_code' =>  $verificationCode,
            'verification_code_expires_at' =>  now()->addMinutes(10)
        ]);

        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешна. Требуется подтверждение email.'
        ], 201);
    }


    public function verifyEmail(VerifyEmailRequest $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email уже подтвержден'
            ], 400);
        }

        if ($user->verification_code != $request->code ||
            $user->verification_code_expires_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный или истекший код подтверждения'
            ], 400);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => 'Email успешно подтвержден',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    private function generateVerificationCode(): int
    {
        return random_int(100000, 999999);
    }

    public function resendCode(ResendCodeRequest $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email уже подтвержден'
            ], 400);
        }

        if ($user->verification_code_expires_at &&
            $user->verification_code_expires_at->diffInMinutes(now()) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через минуту.'
            ], 429);
        }

        $verificationCode = $this->generateVerificationCode();
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = now()->addMinutes(10);
        $user->save();

        $user->notify(new EmailVerificationCodeNotification($verificationCode));

        return response()->json([
            'success' => true,
            'message' => 'Новый код подтверждения отправлен на ваш email'
        ]);
    }

    public function login(LoginRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->login_locked_until && $user->login_locked_until > now()) {
                $minutesLeft = round(now()->diffInMinutes($user->login_locked_until));
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
                if ($user->login_attempts >= 5) {
                    $user->login_locked_until = now()->addMinutes(15);
                    $user->login_attempts = 0;
                    $user->save();

                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много неудачных попыток входа. Доступ заблокирован на 15 минут.',
                        'locked_until' => $user->login_locked_until,
                        'attempts_left' => 0
                    ], 429);
                }

                $attemptsLeft = 5 - $user->login_attempts;
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
                'avatar_url' => $user->avatar_url
            ]
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest  $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->reset_password_code_sent_at &&
            $user->reset_password_code_sent_at->diffInMinutes(now()) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через минуту.',
                'can_resend_after' => 60 - round($user->reset_password_code_sent_at->diffInSeconds(now()))
            ], 429);
        }

        if ($user->reset_password_locked_until && $user->reset_password_locked_until > now()) {
            $minutesLeft = round(now()->diffInMinutes($user->reset_password_locked_until));
            return response()->json([
                'success' => false,
                'message' => "Слишком много неудачных попыток. Попробуйте через {$minutesLeft} минут.",
                'locked_until' => $user->reset_password_locked_until
            ], 429);
        }

        $code = random_int(100000, 999999);

        $user->reset_password_code = $code;
        $user->reset_password_code_expires_at = now()->addMinutes(10);
        $user->reset_password_code_sent_at = now();

        $user->reset_password_attempts = 0;
        $user->reset_password_locked_until = null;

        $user->save();

        $user->notify(new ResetPasswordNotification($code));

        return response()->json([
            'success' => true,
            'message' => 'Код для сброса пароля отправлен на ваш email'
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->reset_password_locked_until && $user->reset_password_locked_until > now()) {
            $minutesLeft = round(now()->diffInMinutes($user->reset_password_locked_until));
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
            $user->reset_password_code = null;
            $user->reset_password_code_expires_at = null;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'Код истек. Запросите новый код.'
            ], 400);
        }

        if ($user->reset_password_code != $request->code) {
            $user->increment('reset_password_attempts');

            if ($user->reset_password_attempts >= 5) {
                $user->reset_password_locked_until = now()->addMinutes(15);
                $user->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Слишком много неудачных попыток. Доступ заблокирован на 15 минут.',
                    'locked_until' => $user->reset_password_locked_until
                ], 429);
            }

            $attemptsLeft = 5 - $user->reset_password_attempts;

            return response()->json([
                'success' => false,
                'message' => "Неверный код. Осталось попыток: {$attemptsLeft}",
                'attempts_left' => $attemptsLeft
            ], 400);
        }

        $user->password = Hash::make($request->password);

        $user->reset_password_code = null;
        $user->reset_password_code_expires_at = null;
        $user->save();

        $user->tokens()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30));

        return response()->json([
            'success' => true,
            'message' => 'Пароль успешно изменен',
            'access_token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url
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

    private function generateAvatar(string $name): string
    {
        $nameForAvatar = urlencode($name);
        return "https://api.dicebear.com/7.x/avataaars/svg?seed={$nameForAvatar}";
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
        ]);
    }
}
