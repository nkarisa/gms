<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Web\Core\Login;
use Config\GrantsConfig;

/**
 * @var RouteCollection $routes
 */

// Web routes

$routes->get('/', [Login::class, 'index']);

$routes->group('login', static function ($routes) {
    $routes->get('logout', [Login::class, 'logout']);
    $routes->get('(:segment)', [Login::class, 'index']);
    $routes->post('(:segment)', [Login::class, 'ajax_login']);
});


$config = config(GrantsConfig::class);
$modules = $config->modules;
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
            $routes->add('showList', $module.'\\'.$controllerName.'::showList');
            $routes->add('list', $module.'\\'.$controllerName.'::list'); 
            $routes->add('view/(:segment)', $module.'\\'.$controllerName.'::view/$1');
            $routes->add('singleFormAdd', $module.'\\'.$controllerName.'::singleFormAdd');  
            $routes->add('multiFormAdd', $module.'\\'.$controllerName.'::multiFormAdd'); 
            $routes->add('edit/(:segment)', $module.'\\'.$controllerName.'::edit/$1'); 
            $routes->add('create',$module.'\\'.$controllerName.'::create');
            $routes->add('update',$module.'\\'.$controllerName.'::update');
            $routes->add('delete',$module.'\\'.$controllerName.'::delete');
        });        
    }
}


// Api Routes
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes) { // 'filter' => 'api-auth', 
    $routes->resource('user');
});
