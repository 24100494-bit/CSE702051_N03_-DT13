<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TANG CAT NGANG - xac thuc va quyen chuc nang (muc 1)
 * Cach dung trong Routes.php:
 *   ['filter' => 'quyen']       -> chi can dang nhap, chua dang nhap tra 401
 *   ['filter' => 'quyen:F2.2']  -> can dang nhap va vai tro co quyen F2.2, thieu quyen tra 403
 * Bang quyen nam o app/Config/PhanQuyen.php. Ma chuc nang khong co trong bang thi chan het.
 */
class PhanQuyenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $nguoiDung = session()->get('nguoi_dung');

        if (! $nguoiDung) {
            return $this->tuChoi(401, 'UNAUTHENTICATED', 'Ban chua dang nhap');
        }

        if (empty($arguments)) {
            return;
        }

        $bangQuyen = config('PhanQuyen')->chucNang;

        foreach ($arguments as $maChucNang) {
            if (array_intersect($nguoiDung['danh_sach_vai_tro'], $bangQuyen[$maChucNang] ?? [])) {
                return;
            }
        }

        return $this->tuChoi(
            403,
            'FORBIDDEN',
            'Ban khong co quyen thuc hien chuc nang nay',
            'nguoi_dung_id=' . $nguoiDung['id'] . ' goi ' . $request->getMethod(true) . ' '
                . $request->getUri()->getPath() . ' can quyen ' . implode(',', $arguments)
        );
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /** Tra loi dung cau truc loi chung; chi tiet chi ghi vao log */
    private function tuChoi(int $status, string $code, string $message, string $chiTietLog = '')
    {
        $requestId = bin2hex(random_bytes(8));

        log_message('warning', '[' . $requestId . '] ' . $code . ' ' . $chiTietLog);

        return service('response')->setStatusCode($status)->setJSON([
            'error' => [
                'code'      => $code,
                'message'   => $message,
                'details'   => [],
                'requestId' => $requestId,
            ],
        ]);
    }
}
