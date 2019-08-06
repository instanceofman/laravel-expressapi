<?php


namespace Isofman\LaravelExpressAPI;


use Illuminate\Routing\Router;

class ExpressAPI
{
    public static function routes()
    {
        Router::get('/express-api', 'Isofman\LaravelExpressAPI\DataResolveController@index');
    }
}