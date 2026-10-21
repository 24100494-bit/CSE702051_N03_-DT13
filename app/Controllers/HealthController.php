<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Repositories\TinhTrangRepository;
use App\Services\TinhTrangService;
use Config\Database;

class HealthController extends BaseController
{
    /** GET /api/v1/health -- khong yeu cau xac thuc; 200 khi ung dung va CSDL deu hoat dong, 503 khi CSDL loi */
    public function index()
    {
        try {
            $service = new TinhTrangService(new TinhTrangRepository(Database::connect()));

            return $this->respondSuccess($service->kiemTra());
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }
}
