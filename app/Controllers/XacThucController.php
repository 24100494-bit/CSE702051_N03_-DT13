<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\ValidationException;
use App\Repositories\GioiHanDangNhapRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\XacThucService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
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

            $hoSo = $this->service()->dangNhap($tenDangNhap, $matKhau, $this->request->getIPAddress());

            // Tao lai ma phien sau khi xac thuc de chan chiem phien
            $session = session();
            $session->regenerate(true);
            $session->set('nguoi_dung', [
                'id'                => (int) $hoSo['id'],
                'danh_sach_vai_tro' => $hoSo['vai_tro'],
            ]);

            return $this->respondSuccess($hoSo);
        } catch (TooManyRequestsException $e) {
            return $this->respondError($e)->setHeader('Retry-After', (string) $e->getRetryAfter());
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/auth/dang-xuat */
    public function dangXuat()
    {
        $nguoiDung = session()->get('nguoi_dung');
        if ($nguoiDung) {
            $this->service()->dangXuat((int) $nguoiDung['id'], $this->request->getIPAddress());
        }
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

    /** PATCH /api/v1/nguoi-dung/toi - F1.5 cap nhat ho ten, email, so dien thoai */
    public function capNhatToi()
    {
        try {
            $input = $this->docThan();
            $data  = [];
            $loi   = [];

            if (array_key_exists('ho_ten', $input)) {
                $data['ho_ten'] = trim((string) $input['ho_ten']);
                if ($data['ho_ten'] === '' || mb_strlen($data['ho_ten']) > 100) {
                    $loi[] = ['field' => 'ho_ten', 'issue' => 'Bat buoc, toi da 100 ky tu'];
                }
            }
            if (array_key_exists('email', $input)) {
                $data['email'] = trim((string) $input['email']);
                if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 100) {
                    $loi[] = ['field' => 'email', 'issue' => 'Email khong hop le'];
                }
            }
            if (array_key_exists('so_dien_thoai', $input)) {
                $sdt                   = trim((string) $input['so_dien_thoai']);
                $data['so_dien_thoai'] = $sdt === '' ? null : $sdt;
                if (mb_strlen($sdt) > 15) {
                    $loi[] = ['field' => 'so_dien_thoai', 'issue' => 'Toi da 15 ky tu'];
                }
            }
            if (! $data && ! $loi) {
                $loi[] = ['field' => 'ho_ten, email, so_dien_thoai', 'issue' => 'Can gui it nhat mot truong de sua'];
            }
            if ($loi) {
                throw new ValidationException($loi);
            }

            return $this->respondSuccess($this->service()->capNhatHoSo($this->nguoiDungHienTai()['id'], $data));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/nguoi-dung/toi/mat-khau - F1.4 doi mat khau */
    public function doiMatKhau()
    {
        try {
            $input  = $this->docThan();
            $matCu  = (string) ($input['mat_khau_cu'] ?? '');
            $matMoi = (string) ($input['mat_khau_moi'] ?? '');

            if ($matCu === '' || strlen($matMoi) < 8 || strlen($matMoi) > 72) {
                throw new ValidationException([['field' => 'mat_khau_cu, mat_khau_moi', 'issue' => 'Bat buoc; mat khau moi tu 8 den 72 ky tu']]);
            }

            $this->service()->doiMatKhau($this->nguoiDungHienTai()['id'], $matCu, $matMoi, $this->request->getIPAddress());

            return $this->respondSuccess(['da_doi' => true]);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): XacThucService
    {
        $db = Database::connect();

        return new XacThucService(new NguoiDungRepository($db), new NhatKyHeThongRepository($db), new GioiHanDangNhapRepository($db));
    }

    /** Nhan ca JSON lan form; JSON hong tra 422 thay vi de loi 500 */
    private function docThan(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            throw new ValidationException([['field' => 'body', 'issue' => 'Than yeu cau khong phai JSON hop le (UTF-8)']]);
        }

        return is_array($json) ? $json : $this->request->getPost();
    }
}
