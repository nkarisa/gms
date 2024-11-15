<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Web\Core\Login;

/**
 * @var RouteCollection $routes
 */

 $routes->get('language/download_language_file/(:segment)/(:segment)', [App\Controllers\Web\Core\Language::class, "downloadLanguageFile/$1/$2"]);
 $routes->post('language/upload_language_file', [App\Controllers\Web\Core\Language::class, "uploadLanguageFile"]);
 

//  $routes->group('login', static function ($routes) {
//     $routes->post('verifyPasswordComplexity', [Login::class, 'verifyPasswordComplexity']);
//     $routes->get('getOffices/(:segment)/(:segment)', [Login::class, 'getOfficesByAccountSystemId/$1/$2']);
//     $routes->get('getUserDepartmentsRolesAndDesignations/(:segment)/(:segment)/(:segment)', [Login::class, 'getUserDepartmentsRolesAndDesignations/$1/$2/$3']);
//     $routes->get('getUserActivatorIds/(:segment)/(:segment)/(:segment)', [Login::class, 'getUserActivatorIds/$1/$2/$3']);
//     $routes->get('getCountryLanguage/(:segment)', [Login::class, 'getCountryLanguage/$1']);
//     $routes->get('getCountryCurrency/(:segment)', [Login::class, 'getCountryCurrency/$1']);
//     $routes->post('verifyValidEmail', [Login::class, 'verifyValidEmail']);
//     $routes->post('emailExists', [Login::class, 'emailExists']);
//     $routes->post('saveCreateAccountData', [Login::class, 'saveCreateAccountData']);
// });