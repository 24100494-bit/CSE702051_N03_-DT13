<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\NguoiDungRepository;
use App\Services\XacThucService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - dang ky, dang nhap, dang xuat, ho so cua toi
 */
class XacThucController extends BaseController
{
    /** POST /api/v1/auth/dang-ky */
    public function dangKy()
    {
        try {
            $input = $this->docThan();

            $loi = [];
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

            $hoSo = $this->service()->dangKy([
                'ten_dang_nhap' => $tenDangNhap,
                'mat_khau'      => $matKhau,
                'ho_ten'        => $hoTen,
                'email'         => $email,
                'so_dien_thoai' => $soDienThoai === '' ? null : $soDienThoai,
            ]);

            return $this->respondSuccess($hoSo, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/auth/dang-nhap */
    public function dangNhap()
    {
        try {
            $input       = $this->docThan();
            $tenDangNhap = trim((string) ($input['ten_dang_nhap'] ?? ''));
            $matKhau     = (string) ($input['mat_khau'] ?? '');

            if ($tenDangNhap === '' || $matKhau === '') {
                throw new ValidationException([['field' => 'ten_dang_nhap, mat_khau', 'issue' => 'Khong duoc de trong']]);
            }

            $hoSo = $this->service()->dangNhap($tenDangNhap, $matKhau);

            // Tao lai ma phien sau khi xac thuc de chan chiem phien
            $session = session();
            $session->regenerate(true);
            $session->set('nguoi_dung', [
                'id'                => (int) $hoSo['id'],
                'danh_sach_vai_tro' => $hoSo['vai_tro'],
            ]);

            return $this->respondSuccess($hoSo);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/auth/dang-xuat */
    public function dangXuat()
    {
        session()->destroy();

        return $this->response->setStatusCode(204);
    }

    /** GET /api/v1/nguoi-dung/toi */
    public function toi()
    {
        try {
            return $this->respondSuccess($this->service()->layHoSo($this->nguoiDungHienTai()['id']));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): XacThucService
    {
        return new XacThucService(new NguoiDungRepository(Database::connect()));
    }

    /** Nhan ca JSON lan form */
    private function docThan(): array
    {
        $json = $this->request->getJSON(true);

        return is_array($json) ? $json : $this->request->getPost();
    }
}
