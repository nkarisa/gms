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
    // $routes->get('/', [Login::class, 'index']);
    $routes->get('(:segment)', [Login::class, 'index']);
    $routes->post('switch_user', [Login::class, 'switchUser']);
    $routes->get('switch_user/(:segment)', [Login::class, 'switchUser/$1']);
    $routes->post('(:segment)', [Login::class, 'ajax_login']);
});

// These routes should be autorouted
$routes->get('language/switch_language/(:segment)', [App\Controllers\Web\Core\Language::class, "switchLanguage/$1"]);
$routes->get('language/download_language_file/(:segment)/(:segment)', [App\Controllers\Web\Core\Language::class, "downloadLanguageFile/$1/$2"]);
$routes->post('language/upload_language_file', [App\Controllers\Web\Core\Language::class, "uploadLanguageFile"]);


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
            $routes->post('showList', $module.'\\'.$controllerName.'::showList');
            $routes->get('list', $module.'\\'.$controllerName.'::list'); 
            $routes->get('view/(:segment)', $module.'\\'.$controllerName.'::view/$1');
            $routes->get('singleFormAdd', $module.'\\'.$controllerName.'::singleFormAdd'); 
            $routes->get('singleFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::singleFormAdd/$1/$2'); 
            $routes->post('singleFormAdd/(:segment)/(:segment)', $module.'\\'.$controllerName.'::singleFormAdd/$1/$2'); 
            $routes->post('singleFormAdd/(:segment)', $module.'\\'.$controllerName.'::singleFormAdd/$1');  
            $routes->post('singleFormAdd', $module.'\\'.$controllerName.'::singleFormAdd');
            $routes->get('multiFormAdd', $module.'\\'.$controllerName.'::multiFormAdd'); 
            $routes->post('multiFormAdd', $module.'\\'.$controllerName.'::multiFormAdd'); 
            $routes->get('edit/(:segment)', $module.'\\'.$controllerName.'::edit/$1'); 
            $routes->get('create',$module.'\\'.$controllerName.'::create');
            $routes->post('edit/(:segment)',$module.'\\'.$controllerName.'::edit/$1');
            $routes->get('delete',$module.'\\'.$controllerName.'::delete');
        });        
    }
}

// Ajax Routes 

$routes->group('ajax', ['namespace' => 'App\Controllers\Web'], static function($routes){
    $routes->post('/','WebController::ajax');
    $routes->get('(:segment)/(:segment)/(:any)','WebController::ajax/$1/$2/$3');
});

// Api Routes
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes) { // 'filter' => 'api-auth', 
    $routes->resource('user');
});
