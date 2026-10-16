<?php

namespace App\Controllers;

use App\Repositories\CongKhaiRepository;
use App\Services\CongKhaiService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - API danh sach de tai cong khai (K10, F2.1), khach goi duoc
 */
class CongKhaiController extends BaseController
{
    /** GET /api/v1/public/de-tai?q=&page=&size= */
    public function index()
    {
        $service = new CongKhaiService(new CongKhaiRepository(Database::connect()));
        $tuKhoa  = $this->request->getGet('q');

        return $this->respondSuccess($service->danhSach(
            (int) ($this->request->getGet('page') ?? 1),
            (int) ($this->request->getGet('size') ?? CongKhaiService::SIZE_MAC_DINH),
            is_string($tuKhoa) ? $tuKhoa : null
        ));
    }
}
