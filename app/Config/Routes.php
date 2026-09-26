<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('dang-nhap', 'TrangController::dangNhap');
$routes->get('trang-chu', 'TrangController::trangChu');

$routes->group('api/v1', static function ($routes) {
    $routes->get('health', 'HealthController::index');

    $routes->post('auth/dang-ky', 'XacThucController::dangKy');
    $routes->post('auth/dang-nhap', 'XacThucController::dangNhap');
    $routes->post('auth/dang-xuat', 'XacThucController::dangXuat', ['filter' => 'quyen']);

    $routes->get('nguoi-dung/toi', 'XacThucController::toi', ['filter' => 'quyen:F1.5']);

    $routes->get('de-tai', 'DeTaiController::index', ['filter' => 'quyen:F2.9']);
    $routes->get('de-tai/(:num)', 'DeTaiController::show/$1', ['filter' => 'quyen:F2.3']);
});
