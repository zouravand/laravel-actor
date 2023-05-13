<?php

namespace Tedon\LaravelActor\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
            NamingHelper::getActor($action) . '_id'   => $this->getActorId($action),
            NamingHelper::getActor($action) . '_type' => $this->getActorType($action) ?? null,
            NamingHelper::getActed($action) . '_at'   => $this->getActedAt($action) ?? null,
        ];
    }

    public function getActor(string $action): ?Model
    {
        return $this->getActorType($action)::find($this->getActorId($action));
    }

    public function getActorId(string $action): int|string
    {
        return $this->{NamingHelper::getActor($action) . '_id'};
    }

    public function getActorType(string $action): ?string
    {
        return $this->{NamingHelper::getActor($action) . '_type'} ?? config('laravel-actor.default_actor_type');
    }

    public function getActedAt(string $action): ?Carbon
    {
        return $this->{NamingHelper::getActed($action) . '_at'} ?? null;
    }

    public function touchAction(string $action, bool $isForce = false): void
    {
        $user = Auth::user();
        if (empty($this->{NamingHelper::getActor($action) . '_id'}) || (
                $this->{NamingHelper::getActor($action) . '_id'} == $user->getAuthIdentifier()
                && $this->{NamingHelper::getActor($action) . '_type'} == get_class($user)
            ) || $isForce) {
            $this->setActor($action, $user);
            $this->setActed($action);
        }
    }

    private function setActor(string $action, ?Authenticatable $user): void
    {
        if ($user && !empty($user->getAuthIdentifier())) {
            $this->{NamingHelper::getActor($action) . '_id'} = $user->getAuthIdentifier();
            $this->{NamingHelper::getActor($action) . '_type'} = get_class($user);
            $this->save();
        }
    }

    private function setActed(string $action): void
    {
        $this->{NamingHelper::getActed($action) . '_at'} = Carbon::now();
        $this->save();
    }

    public function actorable(): array
    {
        return [
            'actions' => []
        ];
    }
}