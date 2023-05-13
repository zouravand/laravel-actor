<?php

namespace Tedon\LaravelActor\Observers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Tedon\LaravelActor\Helpers\NamingHelper;

class ActorObserver
{
    public function created(Model $model): void
    {
        $actions = $this->getActorSetting($model);

        if (Arr::has($actions, 'actions') && in_array('create', $actions['actions'])) {
            /** @var ?Authenticatable $user */
            $user = Auth::user();
            if ($user && !empty($user->getAuthIdentifier())) {
                $this->touchAction('create', true);
            }
        }
    }

    public function updated(Model $model): void
    {
        $actions = $this->getActorSetting($model);

        if (Arr::has($actions, 'actions') && in_array('edit', $actions['actions'])) {
            /** @var ?Authenticatable $user */
            $user = Auth::user();
            if ($user && !empty($user->getAuthIdentifier())) {
                $this->touchAction('edit', true);
            }
        }
    }

    public function getActorSetting(Model $model): array
    {
        return $model->actorable() ?? [];
    }
}