<?php

namespace App\Controllers;

use App\Repositories\NguoiDungRepository;
use App\Services\XacThucService;
use Config\Database;

/**
 * Trang giao dien tam: dang nhap va trang chao sau dang nhap.
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
