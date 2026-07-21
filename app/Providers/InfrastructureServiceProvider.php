<?php

namespace App\Providers;

use App\Domain\Chat\Repositories\MessageRepositoryInterface;
use App\Domain\Identity\Repository\UserSessionRepositoryInterface;
use App\Domain\Profile\Repository\ProfileRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Chat\MessageRepository;
use App\Infrastructure\Persistence\Eloquent\Profile\ProfileRepository;
use App\Infrastructure\Persistence\Eloquent\Session\UserSessionRepository;
use Illuminate\Support\ServiceProvider;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProfileRepositoryInterface::class,
            ProfileRepository::class
        );
        $this->app->bind(
            UserSessionRepositoryInterface::class,
            UserSessionRepository::class
        );
        $this->app->bind(
            MessageRepositoryInterface::class,
            MessageRepository::class
        );

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
