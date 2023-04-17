<?php

namespace Tedon\LaravelActor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static defineMacros()
 */
class Actor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-actor';
    }
}