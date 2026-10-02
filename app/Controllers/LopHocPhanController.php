<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\LopHocPhanRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\LopHocPhanService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - lop hoc phan. Xem: moi vai tro da dang nhap (F3.2); them, sua, xoa: thu ky khoa (F3.1).
 */
class LopHocPhanController extends BaseController
{
    private const HOC_KY = ['HK1', 'HK2', 'HK3'];

    /** GET /api/v1/lop-hoc-phan */
    public function index()
    {
        try {
            return $this->respondSuccess($this->service()->danhSach());
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/lop-hoc-phan/{id} */
    public function show($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->xem($this->layId($id)));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/lop-hoc-phan */
    public function create()
    {
        try {
            $data = $this->kiemTraDuLieu($this->layThanYeuCau(), true);

            return $this->respondSuccess($this->service()->tao($data, $this->nguoiDungHienTai()), 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/lop-hoc-phan/{id} */
    public function update($id = null)
    {
        try {
            $id   = $this->layId($id);
            $data = $this->kiemTraDuLieu($this->layThanYeuCau(), false);

            return $this->respondSuccess($this->service()->capNhat($id, $data, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** DELETE /api/v1/lop-hoc-phan/{id} */
    public function delete($id = null)
    {
        try {
            $this->service()->xoa($this->layId($id), $this->nguoiDungHienTai());

            return $this->response->setStatusCode(204);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** $taoMoi = true thi ca bon truong la bat buoc; PATCH chi kiem cac truong co gui */
    private function kiemTraDuLieu(array $input, bool $taoMoi): array
    {
        $gioiHan = ['ma_lop' => 20, 'ten_lop' => 100, 'nam_hoc' => 20];
        $data    = [];
        $loi     = [];

        foreach ($gioiHan as $truong => $toiDa) {
            if ($taoMoi || array_key_exists($truong, $input)) {
                $giaTri = trim((string) ($input[$truong] ?? ''));
                if ($giaTri === '' || mb_strlen($giaTri) > $toiDa) {
                    $loi[] = ['field' => $truong, 'issue' => 'Bat buoc, toi da ' . $toiDa . ' ky tu'];
                }
                $data[$truong] = $giaTri;
            }
        }
        if ($taoMoi || array_key_exists('hoc_ky', $input)) {
            $hocKy = strtoupper(trim((string) ($input['hoc_ky'] ?? '')));
            if (!in_array($hocKy, self::HOC_KY, true)) {
                $loi[] = ['field' => 'hoc_ky', 'issue' => 'Chi nhan HK1, HK2, HK3'];
            }
            $data['hoc_ky'] = $hocKy;
        }
        if (!$data && !$loi) {
            $loi[] = ['field' => 'ma_lop, ten_lop, hoc_ky, nam_hoc', 'issue' => 'Can gui it nhat mot truong de sua'];
        }
        if ($loi) {
            throw new ValidationException($loi);
        }

        return $data;
    }

    private function service(): LopHocPhanService
    {
        $db = Database::connect();

        return new LopHocPhanService(new LopHocPhanRepository($db), new NhatKyHeThongRepository($db));
    }
}
