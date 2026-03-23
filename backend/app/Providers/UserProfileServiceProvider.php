<?php

namespace App\Providers;

use App\Services\Implementations\UserProfileServiceImpl;
use App\Services\UserProfileService;
use Illuminate\Support\ServiceProvider;

class UserProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserProfileService::class,
            UserProfileServiceImpl::class
        );
    }
}
