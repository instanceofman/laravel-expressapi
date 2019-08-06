<?php


namespace Isofman\LaravelExpressAPI;


class ExpressAPI
{
    public static function routes($path = 'express-api')
    {
        app('router')->get('/' . $path, '\Isofman\LaravelExpressAPI\DataResolveController@index');
    }
}