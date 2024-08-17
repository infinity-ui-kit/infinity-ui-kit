<?php

namespace Tooinfinity\InfinityUiKit;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tooinfinity\InfinityUiKit\Skeleton\SkeletonClass
 */
class InfinityUiKitFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'infinity-ui-kit';
    }
}
