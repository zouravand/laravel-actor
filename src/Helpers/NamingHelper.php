<?php
namespace Tedon\LaravelActor\Helpers;

use DaveChild\TextStatistics\Syllables;
use Illuminate\Support\Str;

class NamingHelper
{
    private static array $vocals = array('a', 'e', 'i', 'o', 'u');

    public static function getActor(string $action): string
    {
        $action = Str::singular(Str::snake($action));

        $syllableCount = (Syllables::syllableCount($action));

        if ($syllableCount > 1 && Str::endsWith($action, 'it')) {
            return self::getActionBase($action) . 'or';
        }
        if (!in_array($action[-1], self::$vocals) && in_array($action[-2], self::$vocals)) {
            return self::getActionBase($action) . 'er';
        }
        if (Str::endsWith($action, 'ate') && $action != 'update') {
            return self::getActionBase($action) . 'or';
        }
        if (!in_array($action[-2], self::$vocals) && Str::endsWith($action, 'e')) {
            return self::getActionBase($action) . 'er';
        }
        if (Str::endsWith($action, 'ct')) {
            return self::getActionBase($action) . 'or';
        }
        if (Str::endsWith($action, 'ss')) {
            return self::getActionBase($action) . 'or';
        }
        if (!in_array($action[-1], self::$vocals) && !in_array($action[-2], self::$vocals)) {
            return self::getActionBase($action) . 'er';
        }
        return self::getActionBase($action) . 'or';
    }

    public static function getActed(string $action): string
    {
        $action = Str::singular(Str::snake($action));

        return self::getActionBase($action) . 'ed';
    }

    private static function getActionBase(string $action): string
    {
        if (Str::endsWith($action, self::$vocals)) {
            $base = substr($action, 0, -1);
        } else {
            $base = $action;
        }
        return $base;
    }
}
