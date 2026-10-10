<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\NhatKyHeThongService;
use Config\Database;

class NhatKyHeThongController extends BaseController
{
    /** GET /api/v1/nhat-ky-he-thong */
    public function index()
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $size = (int) ($this->request->getGet('size') ?? 20);
            $nguoiDungId = $this->request->getGet('nguoi_dung_id');
            $nguoiDungId = $nguoiDungId === null || $nguoiDungId === '' ? null : (int) $nguoiDungId;
            $hanhDong = $this->request->getGet('hanh_dong') ?: null;
            $tuKhoa = $this->request->getGet('tu_khoa') ?: null;
            $tuNgay = $this->request->getGet('tu_ngay') ?: null;
            $denNgay = $this->request->getGet('den_ngay') ?: null;

            return $this->respondSuccess($this->service()->traCuu(
                $page, $size, $nguoiDungId, $hanhDong, $tuKhoa, $tuNgay, $denNgay, $this->nguoiDungHienTai()
            ));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): NhatKyHeThongService
    {
        return new NhatKyHeThongService(new NhatKyHeThongRepository(Database::connect()));
    }
}
