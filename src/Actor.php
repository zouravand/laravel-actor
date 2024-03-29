<?php

namespace Tedon\LaravelActor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Tedon\LaravelActor\Helpers\NamingHelper;

/**
 * @method actor(string $string, mixed $hasType, mixed $hasTimestamp, mixed $indexName, mixed $shouldIndex)
 */
class Actor
{
    public function defineMacros(): void
    {
        $this->defineBaseMacro();
        $this->defineCustomMacros();
    }

    private function defineBaseMacro(): void
    {
        Blueprint::macro(
            'actor',
            function (
                string $action = 'act',
                $hasType = true,
                $hasTimestamp = true,
                $indexName = null,
                $shouldIndex = false
            ) {
                /** @var Blueprint $this */
                $actor = NamingHelper::getActor($action);
                $acted = NamingHelper::getActed($action);

                if (!in_array("{$acted}_id", Arr::pluck($this->getColumns(), 'name'))) {
                    $field = $this->unsignedBigInteger("{$actor}_id")->nullable();
                    if ($hasType) {
                        if (!in_array("{$acted}_type", Arr::pluck($this->getColumns(), 'name'))) {
                            if (config('laravel-actor.use_type_mapping', false)) {
                                $this->unsignedTinyInteger("{$actor}_type")->nullable();
                            } else {
                                $this->string("{$actor}_type")->nullable();
                            }
                        }
                    }
                    if ($hasTimestamp) {
                        if (!in_array("{$acted}_at", Arr::pluck($this->getColumns(), 'name'))) {
                            $this->timestamp("{$acted}_at")->nullable();
                        }
                    }

                    if ($shouldIndex) {
                        if ($hasType) {
                            $this->index(["{$actor}_type", "{$actor}_id"], $indexName);
                        } else {
                            $this->index(["{$actor}_id"], $indexName);
                        }
                    }

                    return $field;
                }
                return null;
            }
        );
    }

    private function defineCustomMacros(): void
    {
        foreach (config('laravel-actor.custom-macros', []) as $customMacro) {
            Blueprint::macro(
                NamingHelper::getActor($customMacro),
                function ($hasType = true, $hasTimestamp = true, $indexName = null, $shouldIndex = false) use (
                    $customMacro
                ) {
                    return $this->actor($customMacro, $hasType, $hasTimestamp, $indexName, $shouldIndex);
                }
            );
        }
    }

}