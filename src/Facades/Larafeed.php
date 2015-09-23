<?php namespace dirkcoetzer\Larafeed\Facades;

use Illuminate\Support\Facades\Facade;

class Larafeed extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'larafeed';
    }
}
