<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Web\Core\Login;

/**
 * @var RouteCollection $routes
 */

 $routes->get('language/download_language_file/(:segment)/(:segment)', [App\Controllers\Web\Core\Language::class, "downloadLanguageFile/$1/$2"]);
 $routes->post('language/upload_language_file', [App\Controllers\Web\Core\Language::class, "uploadLanguageFile"]);
 