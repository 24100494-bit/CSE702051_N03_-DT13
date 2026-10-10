<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TANG CAT NGANG - chong gia mao yeu cau lien trang (CSRF, BM3)
 * Ap cho api/* (Config/Filters.php), chay truoc bo loc quyen. Yeu cau lam doi du lieu
 * (POST, PUT, PATCH, DELETE) ma trinh duyet bao nguon (Origin, neu khong co thi Referer)
 * khac ten mien cua chinh trang thi tra 403. Origin "null" (trang mo tu tep, iframe sandbox) cung bi chan.
 * Yeu cau khong co ca hai header (curl, Postman) khong mang cookie phien cua nan nhan nen cho qua;
 * cookie phien con SameSite=Lax (Config/Cookie.php) lam lop thu hai.
 */
class KiemNguonFilter implements FilterInterface
{
    private const PHUONG_THUC_DOI_DU_LIEU = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! in_array($request->getMethod(true), self::PHUONG_THUC_DOI_DU_LIEU, true)) {
            return;
        }

        $tenMien = strtolower((string) $request->getServer('HTTP_HOST'));
        $origin  = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');

        if ($origin !== '') {
            $nguon = $origin;
        } elseif ($referer !== '') {
            $nguon = $referer;
        } else {
            return;
        }

        if ($tenMien !== '' && $this->tenMienCua($nguon) === $tenMien) {
            return;
        }

        $requestId = bin2hex(random_bytes(8));
        log_message('warning', '[' . $requestId . '] CSRF_REJECTED ' . $request->getMethod(true) . ' '
            . $request->getUri()->getPath() . ' nguon=' . mb_substr($nguon, 0, 200) . ' ip=' . $request->getIPAddress());

        return service('response')->setStatusCode(403)->setJSON([
            'error' => [
                'code'      => 'CSRF_REJECTED',
                'message'   => 'Yeu cau den tu trang khac, bi tu choi',
                'details'   => [],
                'requestId' => $requestId,
            ],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    /** "https://ten.mien:cong/duong-dan" -> "ten.mien:cong"; nguon khong hop le ("null") tra '' */
    private function tenMienCua(string $nguon): string
    {
        $phan = parse_url($nguon);
        if (! is_array($phan) || empty($phan['host'])) {
            return '';
        }

        return strtolower($phan['host']) . (isset($phan['port']) ? ':' . $phan['port'] : '');
    }
}
