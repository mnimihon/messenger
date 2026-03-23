<?php

namespace App\Providers;

use App\Repositories\ConversationRepository;
use App\Repositories\Implementations\ConversationRepositoryImpl;
use Illuminate\Support\ServiceProvider;

class ConversationRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ConversationRepository::class,
            ConversationRepositoryImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
