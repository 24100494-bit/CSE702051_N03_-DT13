<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\TinhTrangRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - tinh trang he thong: ung dung dang chay VA CSDL tra loi thi moi bao ok.
 * CSDL loi thi tra 503; loi that chi ghi vao log, khong tra ra ngoai (BM9).
 */
class TinhTrangService
{
    public function __construct(private TinhTrangRepository $repo)
    {
    }

    public function kiemTra(): array
    {
        try {
            $csdl = $this->repo->csdlTraLoi();
        } catch (Throwable $e) {
            log_message('error', 'HEALTH_DB_FAILED ' . $e->getMessage());
            $csdl = false;
        }

        if (! $csdl) {
            throw new ApiException(503, 'SERVICE_UNAVAILABLE', 'Co so du lieu khong phan hoi');
        }

        return [
            'status'   => 'ok',
            'database' => 'ok',
            'time'     => date('c'),
        ];
    }
}
