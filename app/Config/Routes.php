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

    $routes->get('(:segment)/list',[Dashboard::class, 'list']);  
    $routes->get('(:segment)/view',[Dashboard::class, 'view']);   
    $routes->get('(:segment)/single_form_add',[Dashboard::class, 'single_form_add']);    
    $routes->get('(:segment)/multi_form_add',[Dashboard::class, 'multi_form_add']);
    $routes->get('(:segment)/edit',[Dashboard::class, 'edit']);  
    
    $routes->post('(:segment)/create',[Dashboard::class, 'create']);
    $routes->post('(:segment)/update',[Dashboard::class, 'update']);
    $routes->post('(:segment)/delete',[Dashboard::class, 'delete']);
// });


// Api Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) { // 'filter' => 'api-auth', 
    // $routes->resource('users');
});
