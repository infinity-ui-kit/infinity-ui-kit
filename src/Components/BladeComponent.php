<?php


declare(strict_types=1);

namespace InfinityUiKit\Components;

use Illuminate\View\Component as IlluminateComponent;

abstract class BladeComponent extends IlluminateComponent
{
    /** @var array */
    protected static array $assets = [];

    public static function assets(): array
    {
        return static::$assets;
    }
}