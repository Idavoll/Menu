<?php

use Illuminate\Routing\Router;

/** @var Router $router */

$router->group(['prefix' => '/menuitem'], function (Router $router) {
    $router->post('/update', [
        'as' => 'api.menuitem.update',
        'uses' => 'MenuItemController@update',
    ]);
    $router->post('/delete', [
        'as' => 'api.menuitem.delete',
        'uses' => 'MenuItemController@delete',
    ]);
});
