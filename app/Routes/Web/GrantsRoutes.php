<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

 //http://localhost:9090/budget/view/qa6qNR2Lyw/schedule/kE068JmNdP

 $routes->get('budget/view/(:segment)/(:segment)/(:segment)', [App\Controllers\Web\Grants\Budget::class, "view/$1/$2/$3"]);

 
