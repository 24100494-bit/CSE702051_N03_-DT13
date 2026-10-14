<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Repositories\NhacHanRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\NhacHanService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - tac vu noi bo, chi goi duoc khi qua bo loc khoa noi bo (KhoaNoiBoFilter)
 */
class NoiBoController extends BaseController
{
    /** POST /api/v1/internal/thong-bao/nhac-han - F6.3 */
    public function nhacHan()
    {
        try {
            $db = Database::connect();
            $service = new NhacHanService(new NhacHanRepository($db), new ThongBaoRepository($db), new NhatKyHeThongRepository($db));

            return $this->respondSuccess($service->toanBo());
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }
}
