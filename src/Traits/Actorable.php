<?php

namespace Tedon\LaravelActor\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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

    public function getAct(string $action, ?int $customOffset = null): ?array
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return [
            NamingHelper::getActor($action).'_id'   => $this->getActorId($action),
            NamingHelper::getActor($action).'_type' => $this->getActorType($action) ?? null,
            NamingHelper::getActed($action).'_at'   => $this->getActedAt($action) ?? null,
        ];
    }

    public function getActor(string $action, ?int $customOffset = null): ?Model
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->getActorType($action)::find($this->getActorId($action));
    }

    public function getActorId(string $action, ?int $customOffset = null): int|string|null
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->{NamingHelper::getActor($action).'_id'};
    }

    public function getActorType(string $action, ?int $customOffset = null): ?string
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->{NamingHelper::getActor($action).'_type'} ?? config('laravel-actor.default_actor_type');
    }

    public function getActedAt(string $action, ?int $customOffset = null): ?Carbon
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->{NamingHelper::getActed($action).'_at'} ? Carbon::parse($this->{NamingHelper::getActed($action).'_at'}) : null;
    }

    public function touchAction(string $action, bool $isForce = false): void
    {
        $user = Auth::user();
        if (empty($this->{NamingHelper::getActor($action).'_id'}) || (
                $this->{NamingHelper::getActor($action).'_id'} == $user->getAuthIdentifier()
                && $this->{NamingHelper::getActor($action).'_type'} == get_class($user)
            ) || $isForce) {
            $this->setActor($action, $user);
            $this->setActed($action);
        }
    }

    private function setActor(string $action, ?Authenticatable $user): void
    {
        if ($user && !empty($user->getAuthIdentifier())) {
            if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_id')) {
                $this->{NamingHelper::getActor($action).'_id'} = $user->getAuthIdentifier();
            }
            if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_type')) {
                $this->{NamingHelper::getActor($action).'_type'} = get_class($user);
            }
            $this->saveQuietly();
        }
    }

    private function setActed(string $action): void
    {
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActed($action).'_at')) {
            $this->{NamingHelper::getActed($action).'_at'} = Carbon::now();
        }
        $this->saveQuietly();
    }

    public function cleanAction(string $action): void
    {
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_id')) {
            $this->{NamingHelper::getActor($action).'_id'} = null;
        }
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_type')) {
            $this->{NamingHelper::getActor($action).'_type'} = null;
        }
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActed($action).'_at')) {
            $this->{NamingHelper::getActed($action).'_at'} = null;
        }
        $this->saveQuietly();
    }

    public function isActedBy(string $action, ?Authenticatable $user, ?int $customOffset = null): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->isRecentlyActed($action, $customOffset)) {
            return false;
        }

        return ($user->getAuthIdentifier() == $this->{NamingHelper::getActor($action).'_id'}
            && get_class($user) == $this->{NamingHelper::getActor($action).'_type'});
    }

    public function scopeActedBy(Builder $query, string $action, Authenticatable $user, ?int $customOffset = null): void
    {
        $query->where(function ($query) use ($action, $user, $customOffset) {
            $query->where(NamingHelper::getActor($action).'_id', $user->getAuthIdentifier());
            $query->where(NamingHelper::getActor($action).'_type', get_class($user));
            $query->when(!is_null($customOffset), function ($query) use ($action, $user, $customOffset) {
                $query->where(NamingHelper::getActed($action).'_at', '>=', $this->getOffset($action, $customOffset));
            });
        });
    }

    public function actorable(): array
    {
        return [
            'actions' => []
        ];
    }

    private function isRecentlyActed(string $action, ?int $customOffset = null): bool
    {
        $offset = $this->getOffset($action, $customOffset);

        if ($offset > -1 && $this->{NamingHelper::getActed($action).'_at'} <= Carbon::now()->subHours($offset)) {
            return false;
        }

        return true;
    }

    private function getOffset(string $action, ?int $customOffset): int
    {
        $offset = $customOffset ?? -1;

        if (
            is_null($customOffset)
            && Arr::has($this->actorable(), $action)
            && isset($this->actorable()[$action]['offset'])
        ) {
            $offset = (int) $this->actorable()[$action]['offset'];
        }

        return $offset;
    }
}