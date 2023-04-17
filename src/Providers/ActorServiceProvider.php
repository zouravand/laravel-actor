<?php

namespace Tedon\LaravelActor\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Tedon\LaravelActor\Helpers\NamingHelper;

/**
 * @method actor(string $action, $hasType, $hasTimestamp, $indexName, $shouldIndex)
 */
class ActorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('Laravel Actor', fn() => ['Version' => '0.0.1']);

        $this->defineActorBlueprintMacro();
        $this->defineCustomBlueprintMacro();
    }

    private function defineActorBlueprintMacro(): void
    {
        Blueprint::macro('actor', function (string $action = 'act', $hasType = true, $hasTimestamp = false, $indexName = null, $shouldIndex = false) {
            /** @var Blueprint $this */
            $actor = NamingHelper::getActor($action);
            $acted = NamingHelper::getActed($action);

            $field = $this->unsignedBigInteger("{$actor}_id")->nullable();
            if ($hasType)
                $this->string("{$actor}_type")->nullable();

            if ($hasTimestamp)
                $this->timestamp("{$acted}_at")->nullable();

            if ($shouldIndex) {
                if ($hasType)
                    $this->index(["{$actor}_type", "{$actor}_id"], $indexName);
                else
                    $this->index(["{$actor}_id"], $indexName);
            }

            return $field;
        });
    }

    private function defineCustomBlueprintMacro(): void
    {
        Blueprint::macro('creator', function ($hasType = true, $hasTimestamp = false, $indexName = null, $shouldIndex = false) {
            return $this->actor('create', $hasType, $hasTimestamp, $indexName, $shouldIndex);
        });

        Blueprint::macro('editor', function ($hasType = true, $hasTimestamp = false, $indexName = null, $shouldIndex = false) {
            return $this->actor('edit', $hasType, $hasTimestamp, $indexName, $shouldIndex);
        });
    }
}