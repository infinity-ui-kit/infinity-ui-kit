<?php


declare(strict_types=1);

namespace InfinityUiKit\Components;

use Livewire\Component;

abstract class LivewireComponent extends Component
{
    /** @var array */
    protected static array $assets = [];

    public static function assets(): array
    {
        return static::$assets;
    }
}