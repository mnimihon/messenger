<?php

namespace App\Providers;

use App\Services\Implementations\MessagesServiceImpl;
use App\Services\Implementations\UserServiceImpl;
use App\Services\MessagesService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class MessagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MessagesService::class,
            MessagesServiceImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
