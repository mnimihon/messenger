<?php

namespace App\Providers;

use App\Services\Implementations\PhotoServiceImpl;
use App\Services\PhotoService;
use Illuminate\Support\ServiceProvider;

class PhotoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PhotoService::class,
            PhotoServiceImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
