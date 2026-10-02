<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\DeTaiRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\NhomSinhVienService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - nhom sinh vien. Ma nhom {id} chinh la ma de tai cua nhom.
 * CHI doc tham so, kiem tra dinh dang, goi Service, chon ma HTTP; khong truy van CSDL.
 */
class NhomSinhVienController extends BaseController
{
    /** GET /api/v1/nhom-sinh-vien/{id} - F2.10 */
    public function show($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->xem($this->layId($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/nhom-sinh-vien/{id}/gvhd - F2.8 phan cong GVHD */
    public function phanCongGvhd($id = null)
    {
        try {
            $id     = $this->layId($id);
            $gvhdId = (int) ($this->layThanYeuCau()['gvhd_id'] ?? 0);

            if ($gvhdId <= 0) {
                throw new ValidationException([['field' => 'gvhd_id', 'issue' => 'Bat buoc, la so nguyen duong']]);
            }

            return $this->respondSuccess($this->service()->phanCongGvhd($id, $gvhdId, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/nhom-sinh-vien/{id}/thanh-vien - F2.11 */
    public function themThanhVien($id = null)
    {
        try {
            $id         = $this->layId($id);
            $input      = $this->layThanYeuCau();
            $sinhVienId = (int) ($input['sinh_vien_id'] ?? 0);

            if ($sinhVienId <= 0) {
                throw new ValidationException([['field' => 'sinh_vien_id', 'issue' => 'Bat buoc, la so nguyen duong']]);
            }
            if (($input['vai_tro_nhom'] ?? 'member') !== 'member') {
                throw new ValidationException([['field' => 'vai_tro_nhom', 'issue' => 'Chi them duoc thanh vien (member); nhom truong la nguoi de xuat']]);
            }

            return $this->respondSuccess($this->service()->themThanhVien($id, $sinhVienId, $this->nguoiDungHienTai()), 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** DELETE /api/v1/nhom-sinh-vien/{id}/thanh-vien/{svId} - F2.11 */
    public function xoaThanhVien($id = null, $svId = null)
    {
        try {
            $this->service()->xoaThanhVien($this->layId($id), $this->layId($svId, 'svId'), $this->nguoiDungHienTai());

            return $this->response->setStatusCode(204);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): NhomSinhVienService
    {
        $db = Database::connect();

        return new NhomSinhVienService(
            new DeTaiRepository($db),
            new ThanhVienNhomRepository($db),
            new NguoiDungRepository($db),
            new NhatKyHeThongRepository($db),
            new ThongBaoRepository($db)
        );
    }
}
