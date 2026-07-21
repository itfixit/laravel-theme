<?php namespace Facuz\Theme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Facuz\Theme\Asset
 */
class Asset extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'asset'; }

}
