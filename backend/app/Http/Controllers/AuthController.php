<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerificationCooldownRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Services\AccountAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->register(
            $request->name,
            $request->email,
            $request->password
        ));
    }

    public function verificationCooldown(VerificationCooldownRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->verificationCooldown($request->email));
    }

    public function verifyEmail(VerifyEmailRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->verifyEmail($request->email, $request->code));
    }

    public function resendCode(ResendCodeRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->resendVerificationCode($request->email));
    }

    public function login(LoginRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->login($request->email, $request->password));
    }

    public function forgotPassword(ForgotPasswordRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->forgotPassword($request->email));
    }

    public function resetPassword(ResetPasswordRequest $request, AccountAuthService $accountAuthService)
    {
        return $this->jsonFromService($accountAuthService->resetPassword(
            $request->email,
            $request->code,
            $request->password
        ));
    }

    public function logout(Request $request, AccountAuthService $accountAuthService)
    {
        $accountAuthService->revokeCurrentToken($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Успешный выход',
        ]);
    }

    public function user(Request $request, AccountAuthService $accountAuthService)
    {
        return response()->json($accountAuthService->authenticatedUserPayload($request->user()));
    }
}
