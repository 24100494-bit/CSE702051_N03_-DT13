<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\NguoiDungService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - quan ly tai khoan cua thu ky khoa (F1.6).
 */
class NguoiDungController extends BaseController
{
    /** GET /api/v1/nguoi-dung?vai_tro=&q=&page=&size= */
    public function index()
    {
        try {
            [$page, $size] = $this->layPhanTrang();

            $vaiTro = $this->request->getGet('vai_tro');
            $vaiTro = $vaiTro === null || $vaiTro === '' ? null : $this->vaiTroHopLe((string) $vaiTro);
            $tuKhoa = trim((string) ($this->request->getGet('q') ?? ''));

            return $this->respondSuccess($this->service()->danhSach($page, $size, $vaiTro, $tuKhoa === '' ? null : mb_substr($tuKhoa, 0, 100)));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/nguoi-dung/{id} */
    public function show($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->xem($this->layId($id)));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/nguoi-dung - thu ky tao tai khoan theo vai tro */
    public function create()
    {
        try {
            $input = $this->layThanYeuCau();
            $loi   = [];

            $tenDangNhap = trim((string) ($input['ten_dang_nhap'] ?? ''));
            $matKhau     = (string) ($input['mat_khau'] ?? '');
            $hoTen       = trim((string) ($input['ho_ten'] ?? ''));
            $email       = trim((string) ($input['email'] ?? ''));
            $soDienThoai = trim((string) ($input['so_dien_thoai'] ?? ''));

            if ($tenDangNhap === '' || mb_strlen($tenDangNhap) > 50) {
                $loi[] = ['field' => 'ten_dang_nhap', 'issue' => 'Bat buoc, toi da 50 ky tu'];
            }
            if (strlen($matKhau) < 8 || strlen($matKhau) > 72) {
                $loi[] = ['field' => 'mat_khau', 'issue' => 'Tu 8 den 72 ky tu'];
            }
            if ($hoTen === '' || mb_strlen($hoTen) > 100) {
                $loi[] = ['field' => 'ho_ten', 'issue' => 'Bat buoc, toi da 100 ky tu'];
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
                $loi[] = ['field' => 'email', 'issue' => 'Email khong hop le'];
            }
            if (mb_strlen($soDienThoai) > 15) {
                $loi[] = ['field' => 'so_dien_thoai', 'issue' => 'Toi da 15 ky tu'];
            }
            if ($loi) {
                throw new ValidationException($loi);
            }

            $hoSo = $this->service()->tao([
                'ten_dang_nhap' => $tenDangNhap,
                'mat_khau'      => $matKhau,
                'ho_ten'        => $hoTen,
                'email'         => $email,
                'so_dien_thoai' => $soDienThoai === '' ? null : $soDienThoai,
                'vai_tro'       => $this->vaiTroHopLe((string) ($input['vai_tro'] ?? '')),
            ], $this->nguoiDungHienTai());

            return $this->respondSuccess($hoSo, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/nguoi-dung/{id}/trang-thai {khoa?, vai_tro?} - khoa / mo, doi vai tro */
    public function capNhatTrangThai($id = null)
    {
        try {
            $id    = $this->layId($id);
            $input = $this->layThanYeuCau();

            $khoa = null;
            if (array_key_exists('khoa', $input)) {
                if (!is_bool($input['khoa'])) {
                    throw new ValidationException([['field' => 'khoa', 'issue' => 'Phai la true hoac false']]);
                }
                $khoa = $input['khoa'];
            }
            $vaiTro = array_key_exists('vai_tro', $input) ? $this->vaiTroHopLe((string) $input['vai_tro']) : null;

            if ($khoa === null && $vaiTro === null) {
                throw new ValidationException([['field' => 'khoa, vai_tro', 'issue' => 'Can gui it nhat mot truong']]);
            }

            return $this->respondSuccess($this->service()->capNhatTrangThai($id, $khoa, $vaiTro, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function vaiTroHopLe(string $vaiTro): string
    {
        $vaiTro = strtoupper(trim($vaiTro));

        if (!in_array($vaiTro, NguoiDungService::VAI_TRO_HOP_LE, true)) {
            throw new ValidationException([['field' => 'vai_tro', 'issue' => 'Chi nhan ' . implode(', ', NguoiDungService::VAI_TRO_HOP_LE)]]);
        }

        return $vaiTro;
    }

    private function service(): NguoiDungService
    {
        $db = Database::connect();

        return new NguoiDungService(new NguoiDungRepository($db), new NhatKyHeThongRepository($db));
    }
}
