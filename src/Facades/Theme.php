<?php namespace Facuz\Theme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Facuz\Theme\Theme
 */
class Theme extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'theme'; }

}
