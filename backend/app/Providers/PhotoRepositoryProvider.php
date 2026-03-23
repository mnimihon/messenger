<?php

namespace App\Providers;

use App\Repositories\Implementations\PhotoRepositoryImpl;
use App\Repositories\PhotoRepository;
use Illuminate\Support\ServiceProvider;

class PhotoRepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PhotoRepository::class,
            PhotoRepositoryImpl::class
        );
    }

    public function boot(): void
    {
        //
    }
}
