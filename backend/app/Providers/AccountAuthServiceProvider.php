<?php

namespace App\Providers;

use App\Services\AccountAuthService;
use App\Services\Implementations\AccountAuthServiceImpl;
use Illuminate\Support\ServiceProvider;

class AccountAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AccountAuthService::class,
            AccountAuthServiceImpl::class
        );
    }
}
