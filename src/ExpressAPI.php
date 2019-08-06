<?php


namespace Isofman\LaravelExpressAPI;


class ExpressAPI
{
    public static function routes()
    {
        app('router')->get('/express-api', 'Isofman\LaravelExpressAPI\DataResolveController@index');
    }
}