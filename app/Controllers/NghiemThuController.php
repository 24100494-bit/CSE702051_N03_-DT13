<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\DiemNghiemThuRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\NghiemThuService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - Luong 3: cham diem nghiem thu (UC06)
 * CHI doc tham so, kiem tra dinh dang, goi Service, chon ma HTTP; khong truy van CSDL.
 */
class NghiemThuController extends BaseController
{
    /** GET /api/v1/nghiem-thu/ho-so?trang_thai=completed|accepted - F4.1 */
    public function index()
    {
        try {
            $page      = max(1, (int) ($this->request->getGet('page') ?? 1));
            $size      = (int) ($this->request->getGet('size') ?? 20);
            $trangThai = $this->request->getGet('trang_thai') ?: null;

            if ($size < 1) {
                $size = 20;
            }
            if ($size > 100) {
                $size = 100;
            }

            return $this->respondSuccess($this->service()->danhSachHoSo($page, $size, $trangThai, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai/{id}/ho-so-nghiem-thu - F4.2 */
    public function hoSo($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->hoSo($this->idHopLe($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/de-tai/{id}/diem-nghiem-thu {diem, nhan_xet} - F4.3 */
    public function cham($id = null)
    {
        try {
            $id      = $this->idHopLe($id);
            $than    = $this->docThan();
            $diem    = $than['diem'] ?? null;
            $nhanXet = trim((string) ($than['nhan_xet'] ?? ''));

            if ($diem === null || $diem === '' || !is_numeric($diem)) {
                throw new ValidationException([['field' => 'diem', 'issue' => 'Bat buoc, la so tu 0 den 10']]);
            }

            $ketQua = $this->service()->cham($id, (float) $diem, $nhanXet === '' ? null : $nhanXet, $this->nguoiDungHienTai());

            return $this->respondSuccess($ketQua, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai/{id}/diem-nghiem-thu - F4.4 */
    public function xemDiem($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->xemDiem($this->idHopLe($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/de-tai/{id}/chot-nghiem-thu - F4.5 */
    public function chot($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->chot($this->idHopLe($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): NghiemThuService
    {
        $db = Database::connect();

        return new NghiemThuService(
            new DeTaiRepository($db),
            new DiemNghiemThuRepository($db),
            new ThanhVienNhomRepository($db),
            new BaoCaoMocRepository($db),
            new MocThoiGianRepository($db),
            new NguoiDungRepository($db),
            new NhatKyHeThongRepository($db),
            new ThongBaoRepository($db)
        );
    }

    private function idHopLe($id): int
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new ValidationException([['field' => 'id', 'issue' => 'Phai la so nguyen duong']]);
        }

        return $id;
    }

    /** Nhan ca JSON lan form; JSON hong tra 422 thay vi de loi 500 */
    private function docThan(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            throw new ValidationException([['field' => 'body', 'issue' => 'Than yeu cau khong phai JSON hop le (UTF-8)']]);
        }

        return is_array($json) ? $json : (array) $this->request->getPost();
    }
}
