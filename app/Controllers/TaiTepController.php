<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Services\TaiTepService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - tai tep bao cao moc ve (K6)
 */
class TaiTepController extends BaseController
{
    /** GET /api/v1/bao-cao-moc/{id}/tep - F3.11, tra tep tai ve; loi tra JSON nhu cac API khac */
    public function baoCaoMoc($id = null)
    {
        try {
            $id = (int) $id;
            if ($id <= 0) {
                throw new ValidationException([['field' => 'id', 'issue' => 'Phai la so nguyen duong']]);
            }

            [$tep, $tenTaiVe, $loai] = $this->service()->baoCaoMoc($id, $this->nguoiDungHienTai());

            return $this->response->download($tep, null)
                ->setFileName($tenTaiVe)
                ->setContentType($loai)
                ->setHeader('X-Content-Type-Options', 'nosniff');
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): TaiTepService
    {
        $db = Database::connect();

        return new TaiTepService(
            new BaoCaoMocRepository($db),
            new DeTaiRepository($db),
            new ThanhVienNhomRepository($db),
            new NhatKyHeThongRepository($db),
            WRITEPATH . 'uploads'
        );
    }
}
