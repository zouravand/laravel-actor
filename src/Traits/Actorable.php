<?php

namespace Tedon\LaravelActor\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ItemNotFoundException;
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
        if (!$this->getActorType($action) || !$this->isRecentlyActed($action, $customOffset)) {
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
        if (!$this->getActorType($action) || !$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->getActorType($action)::find($this->getActorId($action));
    }

    public function getActorId(string $action, ?int $customOffset = null): int|string|null
    {
        if (!$this->getActorType($action) || !$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->{NamingHelper::getActor($action).'_id'};
    }

    public function getActorType(string $action, ?int $customOffset = null): ?string
    {
        if (!$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        $type = $this->getActorTypeKey($this->{NamingHelper::getActor($action).'_type'});

        return $type ?? config('laravel-actor.default_actor_type');
    }

    public function getActedAt(string $action, ?int $customOffset = null): ?Carbon
    {
        if (!$this->getActorType($action) || !$this->isRecentlyActed($action, $customOffset)) {
            return null;
        }

        return $this->{NamingHelper::getActed($action).'_at'} ? Carbon::parse($this->{NamingHelper::getActed($action).'_at'}) : null;
    }

    public function touchAction(string $action, bool $isForce = false): void
    {
        $user = Auth::user();
        if (empty($this->{NamingHelper::getActor($action).'_id'}) || (
                $this->{NamingHelper::getActor($action).'_id'} == $user->getAuthIdentifier()
                && $this->{NamingHelper::getActor($action).'_type'} == $this->getActorTypeValue(get_class($user))
            ) || $isForce) {
            $this->setActor($action, $user);
            $this->setActed($action);
        }
    }

    private function setActor(string $action, ?Authenticatable $user): void
    {
        if ($user && !empty($user->getAuthIdentifier())) {
            $parameters = [];
            if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_id')) {
                $parameters[NamingHelper::getActor($action).'_id'] = $user->getAuthIdentifier();
            }
            if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_type')) {
                $parameters[NamingHelper::getActor($action).'_type'] = $this->getActorTypeValue(get_class($user));
            }
            static::where('id', $this->id)->update($parameters);
        }
    }

    private function setActed(string $action): void
    {
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActed($action).'_at')) {
            $parameters = [];
            $parameters[NamingHelper::getActed($action).'_at'] = Carbon::now();
            static::where('id', $this->id)->update($parameters);
        }
    }

    public function cleanAction(string $action): void
    {
        $parameters = [];
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_id')) {
            $parameters[NamingHelper::getActor($action).'_id'] = null;
        }
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActor($action).'_type')) {
            $parameters[NamingHelper::getActor($action).'_type'] = null;
        }
        if (Schema::hasColumn($this->getTable(), NamingHelper::getActed($action).'_at')) {
            $parameters[NamingHelper::getActed($action).'_at'] = null;
        }
        static::where('id', $this->id)->update($parameters);
    }

    public function isActedBy(string $action, ?Authenticatable $user, ?int $customOffset = null): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->getActorType($action) || !$this->isRecentlyActed($action, $customOffset)) {
            return false;
        }

        return ($user->getAuthIdentifier() == $this->{NamingHelper::getActor($action).'_id'}
            && $this->getActorTypeValue(get_class($user)) == $this->{NamingHelper::getActor($action).'_type'});
    }

    public function scopeActedBy(Builder $query, string $action, Authenticatable $user, ?int $customOffset = null): void
    {
        $query->where(function ($query) use ($action, $user, $customOffset) {
            $query->where(NamingHelper::getActor($action).'_id', $user->getAuthIdentifier());
            $query->where(NamingHelper::getActor($action).'_type', $this->getActorTypeValue(get_class($user)));
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

    private function getActorTypeValue(string $className): ?string
    {
        if (!config('laravel-actor.use_type_mapping', false)) {
            return $className;
        }

        if (Arr::has(config('laravel-actor.type_mapping'), 0)) {
            throw new ItemNotFoundException();
        }

        $type = array_filter(config('laravel-actor.type_mapping'),
            function (?string $value, ?string $key) use ($className) {
                if (!is_string($key) || !is_numeric($key)) {
                    return false;
                }

                return $value == $className;
            }, ARRAY_FILTER_USE_BOTH);

        return Arr::first(array_keys($type));
    }

    private function getActorTypeKey(?string $typeIndexString): ?string
    {
        if (!config('laravel-actor.use_type_mapping', false)) {
            return $typeIndexString;
        }

        $type = array_filter(config('laravel-actor.type_mapping'),
            function (?string $value, ?string $key) use ($typeIndexString) {
                if (!is_string($key) || !is_numeric($key)) {
                    return false;
                }

                return $key == $typeIndexString;
            }, ARRAY_FILTER_USE_BOTH);

        return Arr::first(array_values($type));
    }
}