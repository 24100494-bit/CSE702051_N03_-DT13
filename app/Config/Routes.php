<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Trang goc vao thang trang dang nhap (khong de trang chao mac dinh cua framework, BM9)
$routes->get('/', 'TrangController::dangNhap');
// curl -I gui HEAD: tra dung trang va the tieu de bao mat thay vi 404 (BM12)
$routes->head('/', 'TrangController::dangNhap');

$routes->get('dang-nhap', 'TrangController::dangNhap');
$routes->get('trang-chu', 'TrangController::trangChu');

$routes->group('api/v1', static function ($routes) {
    $routes->get('health', 'HealthController::index');

    $routes->post('auth/dang-ky', 'XacThucController::dangKy');
    $routes->post('auth/dang-nhap', 'XacThucController::dangNhap');
    $routes->post('auth/dang-xuat', 'XacThucController::dangXuat', ['filter' => 'quyen']);

    $routes->get('nguoi-dung/toi', 'XacThucController::toi', ['filter' => 'quyen:F1.5']);
    $routes->patch('nguoi-dung/toi', 'XacThucController::capNhatToi', ['filter' => 'quyen:F1.5']);
    $routes->patch('nguoi-dung/toi/mat-khau', 'XacThucController::doiMatKhau', ['filter' => 'quyen:F1.4']);

    // Quan ly tai khoan (thu ky khoa)
    $routes->get('nguoi-dung', 'NguoiDungController::index', ['filter' => 'quyen:F1.6']);
    $routes->post('nguoi-dung', 'NguoiDungController::create', ['filter' => 'quyen:F1.6']);
    $routes->get('nguoi-dung/(:num)', 'NguoiDungController::show/$1', ['filter' => 'quyen:F1.6']);
    $routes->patch('nguoi-dung/(:num)/trang-thai', 'NguoiDungController::capNhatTrangThai/$1', ['filter' => 'quyen:F1.6']);

    $routes->get('de-tai', 'DeTaiController::index', ['filter' => 'quyen:F2.9']);
    $routes->get('de-tai/(:num)', 'DeTaiController::show/$1', ['filter' => 'quyen:F2.3']);
    $routes->get('de-tai/(:num)/lich-su', 'DeTaiController::lichSu/$1', ['filter' => 'quyen:F3.10']);

    // Luong 1: de xuat va duyet de tai (UC01, UC02)
    $routes->post('de-tai', 'DeTaiController::create', ['filter' => 'quyen:F2.2']);
    $routes->patch('de-tai/(:num)', 'DeTaiController::update/$1', ['filter' => 'quyen:F2.4']);
    $routes->delete('de-tai/(:num)', 'DeTaiController::delete/$1', ['filter' => 'quyen:F2.5']);
    $routes->post('de-tai/(:num)/duyet', 'DeTaiController::duyet/$1', ['filter' => 'quyen:F2.6']);
    $routes->post('de-tai/(:num)/tu-choi', 'DeTaiController::tuChoi/$1', ['filter' => 'quyen:F2.7']);

    // Luong 2: nop va duyet bao cao theo moc (UC03, UC04)
    $routes->get('de-tai/(:num)/bao-cao-moc', 'BaoCaoMocController::index/$1', ['filter' => 'quyen:F3.6']);
    $routes->post('de-tai/(:num)/bao-cao-moc', 'BaoCaoMocController::create/$1', ['filter' => 'quyen:F3.4']);
    $routes->get('bao-cao-moc/(:num)', 'BaoCaoMocController::show/$1', ['filter' => 'quyen:F3.6']);
    $routes->get('bao-cao-moc/(:num)/lich-su', 'BaoCaoMocController::lichSu/$1', ['filter' => 'quyen:F3.10']);
    $routes->get('bao-cao-moc/(:num)/tep', 'TaiTepController::baoCaoMoc/$1', ['filter' => 'quyen:F3.11']);
    $routes->post('bao-cao-moc/(:num)', 'BaoCaoMocController::nopLai/$1', ['filter' => 'quyen:F3.5']);
    $routes->post('bao-cao-moc/(:num)/duyet', 'BaoCaoMocController::duyet/$1', ['filter' => 'quyen:F3.7']);
    $routes->post('bao-cao-moc/(:num)/yeu-cau-bo-sung', 'BaoCaoMocController::yeuCauBoSung/$1', ['filter' => 'quyen:F3.8']);

    // Luong 3: cham diem nghiem thu (UC06)
    $routes->get('nghiem-thu/ho-so', 'NghiemThuController::index', ['filter' => 'quyen:F4.1']);
    $routes->get('de-tai/(:num)/ho-so-nghiem-thu', 'NghiemThuController::hoSo/$1', ['filter' => 'quyen:F4.2']);
    $routes->post('de-tai/(:num)/diem-nghiem-thu', 'NghiemThuController::cham/$1', ['filter' => 'quyen:F4.3']);
    $routes->get('de-tai/(:num)/diem-nghiem-thu', 'NghiemThuController::xemDiem/$1', ['filter' => 'quyen:F4.4']);
    $routes->post('de-tai/(:num)/chot-nghiem-thu', 'NghiemThuController::chot/$1', ['filter' => 'quyen:F4.5']);

    // Bao cao thong ke (K7): {loai} = de-tai-theo-lop | tien-do-nop | gvhd
    $routes->get('thong-ke/(:segment)', 'ThongKeController::index/$1', ['filter' => 'quyen:F5.1']);
    $routes->get('thong-ke/(:segment)/xuat', 'ThongKeController::xuat/$1', ['filter' => 'quyen:F5.2']);

    // Nhom sinh vien: ma nhom {id} la ma de tai cua nhom
    $routes->get('nhom-sinh-vien/(:num)', 'NhomSinhVienController::show/$1', ['filter' => 'quyen:F2.10']);
    $routes->patch('nhom-sinh-vien/(:num)/gvhd', 'NhomSinhVienController::phanCongGvhd/$1', ['filter' => 'quyen:F2.8']);
    $routes->post('nhom-sinh-vien/(:num)/thanh-vien', 'NhomSinhVienController::themThanhVien/$1', ['filter' => 'quyen:F2.11']);
    $routes->delete('nhom-sinh-vien/(:num)/thanh-vien/(:num)', 'NhomSinhVienController::xoaThanhVien/$1/$2', ['filter' => 'quyen:F2.11']);

    // Lop hoc phan va moc thoi gian chung
    $routes->get('lop-hoc-phan', 'LopHocPhanController::index', ['filter' => 'quyen:F3.2']);
    $routes->get('lop-hoc-phan/(:num)', 'LopHocPhanController::show/$1', ['filter' => 'quyen:F3.2']);
    $routes->post('lop-hoc-phan', 'LopHocPhanController::create', ['filter' => 'quyen:F3.1']);
    $routes->patch('lop-hoc-phan/(:num)', 'LopHocPhanController::update/$1', ['filter' => 'quyen:F3.1']);
    $routes->delete('lop-hoc-phan/(:num)', 'LopHocPhanController::delete/$1', ['filter' => 'quyen:F3.1']);
    $routes->get('moc-thoi-gian', 'MocThoiGianController::index', ['filter' => 'quyen:F3.2']);
    $routes->post('moc-thoi-gian', 'MocThoiGianController::create', ['filter' => 'quyen:F3.1']);
    $routes->patch('moc-thoi-gian/(:num)', 'MocThoiGianController::update/$1', ['filter' => 'quyen:F3.3']);
    $routes->delete('moc-thoi-gian/(:num)', 'MocThoiGianController::delete/$1', ['filter' => 'quyen:F3.1']);

    // Thong bao cua toi
    $routes->get('nhat-ky-he-thong', 'NhatKyHeThongController::index', ['filter' => 'quyen:F5.3']);

    $routes->get('thong-bao', 'ThongBaoController::index', ['filter' => 'quyen:F6.1']);
    $routes->patch('thong-bao/(:num)/da-doc', 'ThongBaoController::daDoc/$1', ['filter' => 'quyen:F6.2']);
});
