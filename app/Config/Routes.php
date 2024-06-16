<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Core\Login;
use App\Controllers\Core\Dashboard;

/**
 * @var RouteCollection $routes
 */

// Web routes

// $routes->group('web', static function ($routes) {

    $routes->get('/', [Login::class, 'index']);

    $routes->group('login', static function ($routes) {
        $routes->get('logout', [Login::class, 'logout']);
        $routes->get('(:segment)', [Login::class, 'index']);
        $routes->post('(:segment)', [Login::class, 'ajax_login']);
    });

    $routes->get('dashboard/list',[Dashboard::class, 'index'],['as' => 'dashboard']);    
// });


// Api Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) { // 'filter' => 'api-auth', 
    // $routes->resource('users');
});
