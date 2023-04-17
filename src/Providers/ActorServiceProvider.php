<?php

namespace Tedon\LaravelActor\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Tedon\LaravelActor\Actor;
use Tedon\LaravelActor\Facades\Actor as ActorFacade;

/**
 * @method actor(string $action, $hasType, $hasTimestamp, $indexName, $shouldIndex)
 */
class ActorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../resources/config/laravel-actor.php' => config_path('laravel-actor.php')
        ], 'actor-config');

        AboutCommand::add('Laravel Actor', fn() => ['Version' => '0.0.1']);

        ActorFacade::defineMacros();
    }

    public function register(): void
    {
        $this->app->bind('laravel-actor', function () {
            return new Actor();
        });
    }
}