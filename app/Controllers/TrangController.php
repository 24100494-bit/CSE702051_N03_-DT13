<?php

namespace App\Controllers;

use App\Repositories\CongKhaiRepository;
use App\Repositories\NguoiDungRepository;
use App\Services\CongKhaiService;
use App\Services\XacThucService;
use Config\Database;

/**
 * Trang giao dien: trang cong khai (K10), dang nhap va trang chu sau dang nhap.
 * Dang nhap/dang xuat van goi API /api/v1/auth/* tu trinh duyet, controller nay chi tra ve trang.
 */
class TrangController extends BaseController
{
    private const TEN_VAI_TRO = [
        'sinh_vien'   => 'Sinh viên',
        'gvhd'        => 'Giảng viên hướng dẫn',
        'thu_ky_khoa' => 'Thư ký khoa',
        'hoi_dong'    => 'Hội đồng nghiệm thu',
    ];

    /**
     * GET / - trang cong khai (K10): danh sach de tai da duyet, khong can dang nhap.
     * Dung san o may chu (khong can JavaScript) de cong cu tim kiem doc duoc noi dung; ?q= tim kiem, ?trang= phan trang.
     */
    public function congKhai()
    {
        $tuKhoa = $this->request->getGet('q');
        $tuKhoa = is_string($tuKhoa) ? trim($tuKhoa) : '';
        $trang  = max(1, (int) ($this->request->getGet('trang') ?? 1));

        $ketQua = (new CongKhaiService(new CongKhaiRepository(Database::connect())))->danhSach($trang, CongKhaiService::SIZE_MAC_DINH, $tuKhoa);
        $uri    = $this->request->getUri();

        return view('trang/cong-khai', [
            'ketQua'     => $ketQua,
            'tuKhoa'     => $tuKhoa,
            'daDangNhap' => (bool) session()->get('nguoi_dung'),
            'diaChiGoc'  => ($this->request->isSecure() ? 'https' : 'http') . '://' . $uri->getAuthority() . '/',
        ]);
    }

    /** GET /dang-nhap */
    public function dangNhap()
    {
        if (session()->get('nguoi_dung')) {
            return redirect()->to('/trang-chu');
        }

        return view('trang/dang-nhap');
    }

    /** GET /trang-chu */
    public function trangChu()
    {
        $nguoiDung = session()->get('nguoi_dung');

        if (! $nguoiDung) {
            return redirect()->to('/dang-nhap');
        }

        $service = new XacThucService(new NguoiDungRepository(Database::connect()));
        $hoSo    = $service->layHoSo((int) $nguoiDung['id']);

        return view('trang/trang-chu', [
            'hoSo'     => $hoSo,
            'dsVaiTro' => array_map(static fn ($v) => self::TEN_VAI_TRO[$v] ?? $v, $hoSo['vai_tro']),
        ]);
    }
}
