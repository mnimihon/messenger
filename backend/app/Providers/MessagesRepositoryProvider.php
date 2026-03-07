<?php

namespace App\Providers;

use App\Repositories\Implementations\MessagesRepositoryImpl;
use App\Repositories\MessagesRepository;
use Illuminate\Support\ServiceProvider;

class MessagesRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            MessagesRepository::class,
            MessagesRepositoryImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
