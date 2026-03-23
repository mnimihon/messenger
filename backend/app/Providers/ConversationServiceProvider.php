<?php

namespace App\Providers;

use App\Services\ConversationService;
use App\Services\Implementations\ConversationServiceImpl;
use Illuminate\Support\ServiceProvider;

class ConversationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ConversationService::class,
            ConversationServiceImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
