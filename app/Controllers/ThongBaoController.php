<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Repositories\ThongBaoRepository;
use App\Services\ThongBaoService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - thong bao cua toi (F6.1, F6.2).
 */
class ThongBaoController extends BaseController
{
    /** GET /api/v1/thong-bao?page=&size=&chua_doc=1 */
    public function index()
    {
        try {
            [$page, $size] = $this->layPhanTrang();
            $chiChuaDoc    = filter_var($this->request->getGet('chua_doc') ?? false, FILTER_VALIDATE_BOOLEAN);

            return $this->respondSuccess($this->service()->cuaToi($page, $size, $chiChuaDoc, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/thong-bao/{id}/da-doc */
    public function daDoc($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->danhDauDaDoc($this->layId($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): ThongBaoService
    {
        return new ThongBaoService(new ThongBaoRepository(Database::connect()));
    }
}
