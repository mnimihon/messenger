<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{

    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];


    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];


    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }

    public function render($request, Throwable $e)
    {
        if ($this->isApiRequest($request)) {
            Log::error('get_class : ' . get_class($e));
            Log::error($e);

            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            $status = 500;
            $message = 'Произошла внутренняя ошибка сервера';

            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $e->errors()
                ], 422);
            }

            if ($e instanceof ModelNotFoundException) {
                $status = 404;
                $message = 'Ресурс не найден';
            }

            if ($e instanceof NotFoundHttpException) {
                $status = 404;
                $message = 'Маршрут не найден';
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                $status = 405;
                $message = 'Метод не поддерживается';
            }

            if ($e instanceof AuthenticationException) {
                $status = 401;
                $message = 'Не аутентифицирован';
            }

            if ($e instanceof AuthorizationException) {
                $status = 403;
                $message = 'Доступ запрещен';
            }

            if ($e instanceof HttpException) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: 'Ошибка HTTP';
            }

            if (config('app.env') === 'production' && $status === 500) {
                $message = 'Произошла внутренняя ошибка сервера';
            }

            return response()->json([
                'success' => false,
                'message' => $message
            ], $status);
        }

        return parent::render($request, $e);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}
