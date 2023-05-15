<?php

namespace Tedon\LaravelActor\Observers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Tedon\LaravelActor\Traits\Actorable;

class ActorObserver
{
    public function created(Model $model): void
    {
        $actions = $this->getActorSetting($model);

        /** @var Actorable $model */
        if (Arr::has($actions, 'actions') && in_array('create', $actions['actions'])) {
            /** @var ?Authenticatable $user */
            $user = Auth::user();
            if ($user && !empty($user->getAuthIdentifier())) {
                $model->touchAction('create', true);
            }
        }
    }

    public function updated(Model $model): void
    {
        $actions = $this->getActorSetting($model);

        /** @var Actorable $model */
        if (Arr::has($actions, 'actions') && in_array('edit', $actions['actions'])) {
            /** @var ?Authenticatable $user */
            $user = Auth::user();
            if ($user && !empty($user->getAuthIdentifier())) {
                $model->touchAction('edit', true);
            }
        }
    }

    public function getActorSetting(Model $model): array
    {
        /** @var Actorable $model */
        return $model->actorable() ?? [];
    }
}