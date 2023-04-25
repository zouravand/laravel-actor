<?php

namespace Tedon\LaravelActor\Traits;

use Illuminate\Database\Eloquent\Model;
use Tedon\LaravelActor\Helpers\NamingHelper;
use Tedon\LaravelActor\Observers\ActorObserver;

/**
 * @method static observe(string $class)
 */
trait Actorable
{
    public static function bootActorable(): void
    {
        static::observe(ActorObserver::class);
    }

    public function getAct(string $action): array
    {
        return [
            NamingHelper::getActor($action) . '_id' => $this->getActorId($action),
            NamingHelper::getActor($action) . '_type' => $this->getActorType($action) ?? null,
            NamingHelper::getActed($action) . '_at' => $this->getActedAt($action) ?? null,
        ];
    }

    public function getActor(string $action): ?Model
    {
        return $this->getActorType($action)::find($this->getActorId($action));
    }

    public function getActorId(string $action)
    {
        return $this->{NamingHelper::getActor($action) . '_id'};
    }

    public function getActorType(string $action)
    {
        return $this->{NamingHelper::getActor($action) . '_type'} ?? config('laravel-actor.default_actor_type');
    }

    public function getActedAt(string $action)
    {
        return $this->{NamingHelper::getActed($action) . '_at'} ?? null;
    }

    public function actorable(): array
    {
        return [
            'actions' => []
        ];
    }
}