<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Web\Core\Login;

/**
 * @var RouteCollection $routes
 */

// Web routes

// helper('grants');

$routes->get('/', [Login::class, 'index']);

$routes->group('ajax/login', static function ($routes) {
    $routes->post('(:segment)',[Login::class,'$1']);
    $routes->post('(:segment)/(:any)',[Login::class,'$1/$2']);
    $routes->post('(:segment)/(:any)/(:any)',[Login::class,'$1/$2/$3']);
    $routes->post('(:segment)/(:any)/(:any)/(:any)',[Login::class,'$1/$2/$3/$4']);

    $routes->get('(:segment)',[Login::class,'$1']);
    $routes->get('(:segment)/(:any)',[Login::class,'$1/$2']);
    $routes->get('(:segment)/(:any)/(:any)',[Login::class,'$1/$2/$3']);
    $routes->get('(:segment)/(:any)/(:any)/(:any)',[Login::class,'$1/$2/$3/$4']);
});

$routes->group('login', static function ($routes) {
    $routes->get('logout', [Login::class, 'logout']);
    $routes->post('switch_user', [Login::class, 'switchUser']);
    $routes->get('switch_user/(:segment)', [Login::class, 'switchUser/$1']);
    $routes->get('forgot_password', [Login::class, 'forgotPassword']);
    $routes->get('create_account', [Login::class, 'createAccount']);
    $routes->get('forgot_password', [Login::class, 'forgotPassword']);
    $routes->get('reset_password/(:any)', [Login::class, 'resetPassword']);
    $routes->post('ajax_forgot_password', [Login::class, 'ajax_forgot_password']);
    $routes->post('ajax_reset_password', [Login::class, 'ajax_reset_password']);
    $routes->post('(:segment)', [Login::class, 'ajax_login']);
    $routes->get('(:segment)', [Login::class, 'index']);
    
});

$modules = decode_setting("GrantsConfig","modules");
unset($modules[array_search('system', $modules)]);
$grantsLibrary = new \App\Libraries\System\GrantsLibrary();

foreach ($modules as $module){
    $moduleTablesSchema = $grantsLibrary->getPackageSchema($module);
    $moduleTables = array_keys($moduleTablesSchema);
    $module = pascalize($module);
    
    foreach($moduleTables as $moduleTable){
        $routeBase = strtolower($moduleTable);
        $controllerName = pascalize($moduleTable);
        $routes->group($routeBase,['namespace' => 'App\Controllers\Web'], static function ($routes) use ($controllerName, $module) {
            $routes->post('showList', $module.'\\'.$controllerName.'::showList');
            $routes->get('list', $module.'\\'.$controllerName.'::list'); 
            $routes->get('view/(:segment)', $module.'\\'.$controllerName.'::view/$1');
            $routes->get('singleFormAdd', $module.'\\'.$controllerName.'::singleFormAdd'); 
            //$routes->get('budget/view/(:segment)/summary/(:segment)', $module.'\\'.$controllerName.'::view/$1/$2');

            $routes->get('singleFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::singleFormAdd/$1/$2'); 
            $routes->get('multiFormAdd', $module.'\\'.$controllerName.'::multiFormAdd'); 
            $routes->get('multiFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::multiFormAdd/$1/$2'); 
            $routes->post('singleFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::create/$1/$2'); 
            $routes->post('singleFormAdd', $module.'\\'.$controllerName.'::create');
            $routes->post('multiFormAdd', $module.'\\'.$controllerName.'::create'); 
            $routes->post('multiFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::create/$1/$2'); 
            $routes->get('edit/(:segment)', $module.'\\'.$controllerName.'::edit/$1'); 
            // $routes->get('create',$module.'\\'.$controllerName.'::create');
            $routes->post('edit/(:segment)',$module.'\\'.$controllerName.'::update/$1');
            $routes->get('delete',$module.'\\'.$controllerName.'::delete');
            $routes->post('update_item_status/(:segment)',$module.'\\'.$controllerName.'::updateItemStatus/$1');
        });  
        
        $routes->group("ajax/$routeBase", ['namespace' => 'App\Controllers\Web'], static function($routes) use ($controllerName, $module){
            $routes->post('(:segment)',$module.'\\'.$controllerName.'::$1');
            $routes->post('(:segment)/(:any)',$module.'\\'.$controllerName.'::$1/$2');
            $routes->post('(:segment)/(:any)/(:any)',$module.'\\'.$controllerName.'::$1/$2/$2/$3');
            $routes->post('(:segment)/(:any)/(:any)/(:any)',$module.'\\'.$controllerName.'::$1/$2/$3/$4');

            $routes->get('(:segment)',$module.'\\'.$controllerName.'::$1');
            $routes->get('(:segment)/(:any)',$module.'\\'.$controllerName.'::$1/$2');
            $routes->get('(:segment)/(:any)/(:any)',$module.'\\'.$controllerName.'::$1/$2/$2/$3');
            $routes->get('(:segment)/(:any)/(:any)/(:any)',$module.'\\'.$controllerName.'::$1/$2/$3/$4');
        });
    }
}

// Ajax Routes 

$routes->group('ajaxRequest', ['namespace' => 'App\Controllers\Web'], static function($routes){
    $routes->post('/','WebController::ajaxRequest');
    $routes->post('(:segment)','WebController::ajaxRequest');
    $routes->get('(:segment)/(:segment)/(:any)','WebController::ajaxRequest/$1/$2/$3');
});

// Api Routes
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes) { // 'filter' => 'api-auth', 
    $routes->resource('user');
});


// $routes->setAutoRoute(true);