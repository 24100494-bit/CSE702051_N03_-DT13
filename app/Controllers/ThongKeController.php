<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThongKeRepository;
use App\Services\ThongKeService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - ba bao cao tong hop (K7)
 * {loai}: de-tai-theo-lop | tien-do-nop | gvhd; tham so tu_ngay, den_ngay dang YYYY-MM-DD.
 */
class ThongKeController extends BaseController
{
    /** GET /api/v1/thong-ke/{loai}?tu_ngay=&den_ngay= - F5.1 */
    public function index($loai = null)
    {
        try {
            return $this->respondSuccess($this->service()->xem(
                (string) $loai,
                $this->request->getGet('tu_ngay') ?: null,
                $this->request->getGet('den_ngay') ?: null,
                $this->nguoiDungHienTai()
            ));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/thong-ke/{loai}/xuat?dinh_dang=csv&tu_ngay=&den_ngay= - F5.2, tra tep tai ve */
    public function xuat($loai = null)
    {
        try {
            $dinhDang = $this->request->getGet('dinh_dang') ?: 'csv';
            if ($dinhDang !== 'csv') {
                throw new ValidationException([['field' => 'dinh_dang', 'issue' => 'Chi ho tro csv']]);
            }

            [$tenTep, $noiDung] = $this->service()->xuatCsv(
                (string) $loai,
                $this->request->getGet('tu_ngay') ?: null,
                $this->request->getGet('den_ngay') ?: null,
                $this->nguoiDungHienTai()
            );

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $tenTep . '"')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($noiDung);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): ThongKeService
    {
        $db = Database::connect();

        return new ThongKeService(new ThongKeRepository($db), new NhatKyHeThongRepository($db));
    }
}
