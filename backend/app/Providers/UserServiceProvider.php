<?php

namespace App\Providers;

use App\Services\Implementations\UserServiceImpl;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserService::class,
            UserServiceImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
