<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api/v1', function ($routes) {
    $routes->get('health', 'HealthController::index');

    $routes->get('de-tai', 'DeTaiController::index');
    $routes->get('de-tai/(:num)', 'DeTaiController::show/$1');
}
);