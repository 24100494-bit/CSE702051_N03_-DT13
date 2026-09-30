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

    // Luong 1: de xuat va duyet de tai (UC01, UC02)
    $routes->post('de-tai', 'DeTaiController::create', ['filter' => 'quyen:F2.2']);
    $routes->patch('de-tai/(:num)', 'DeTaiController::update/$1', ['filter' => 'quyen:F2.4']);
    $routes->delete('de-tai/(:num)', 'DeTaiController::delete/$1', ['filter' => 'quyen:F2.5']);
    $routes->post('de-tai/(:num)/duyet', 'DeTaiController::duyet/$1', ['filter' => 'quyen:F2.6']);
    $routes->post('de-tai/(:num)/tu-choi', 'DeTaiController::tuChoi/$1', ['filter' => 'quyen:F2.7']);

    // Luong 2: nop va duyet bao cao theo moc (UC03, UC04)
    $routes->post('de-tai/(:num)/bao-cao-moc', 'BaoCaoMocController::create/$1', ['filter' => 'quyen:F3.4']);
    $routes->get('bao-cao-moc/(:num)', 'BaoCaoMocController::show/$1', ['filter' => 'quyen:F3.6']);
    $routes->post('bao-cao-moc/(:num)', 'BaoCaoMocController::nopLai/$1', ['filter' => 'quyen:F3.5']);
    $routes->post('bao-cao-moc/(:num)/duyet', 'BaoCaoMocController::duyet/$1', ['filter' => 'quyen:F3.7']);
    $routes->post('bao-cao-moc/(:num)/yeu-cau-bo-sung', 'BaoCaoMocController::yeuCauBoSung/$1', ['filter' => 'quyen:F3.8']);
});
