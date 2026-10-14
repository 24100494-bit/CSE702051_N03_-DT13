<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TANG CAT NGANG - bao ve diem cuoi noi bo api/v1/internal/* (F3.9, F6.3): khong vai tro nguoi dung nao goi duoc.
 * Chi cho qua khi header X-Khoa-Noi-Bo trung khoa bi mat noibo.khoa trong .env cua may chu.
 * Chua cau hinh khoa thi tu choi tat ca. Sai hoac thieu khoa: da dang nhap tra 403, chua dang nhap tra 401
 * (dung ma trong ma tran phan quyen, nhom G5).
 */
class KhoaNoiBoFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $khoa   = (string) env('noibo.khoa', '');
        $guiLen = $request->getHeaderLine('X-Khoa-Noi-Bo');

        if ($khoa !== '' && $guiLen !== '' && hash_equals($khoa, $guiLen)) {
            return;
        }

        $daDangNhap = (bool) session()->get('nguoi_dung');
        $requestId  = bin2hex(random_bytes(8));
        log_message('warning', '[' . $requestId . '] INTERNAL_REJECTED ' . $request->getUri()->getPath() . ' ip=' . $request->getIPAddress());

        return service('response')->setStatusCode($daDangNhap ? 403 : 401)->setJSON([
            'error' => [
                'code'      => $daDangNhap ? 'FORBIDDEN' : 'UNAUTHENTICATED',
                'message'   => 'Diem cuoi noi bo, khong danh cho nguoi dung',
                'details'   => [],
                'requestId' => $requestId,
            ],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
