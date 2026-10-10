<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TANG CAT NGANG - the tieu de bao mat cho moi phan hoi (BM12), dat trong $globals['after'] cua Config/Filters.php.
 * CSP chi cho tai script, style, anh tu chinh trang: trang khong con script/style viet thang trong HTML
 * (da chuyen ra public/assets), nen ma chen vao trang (XSS) khong chay duoc.
 */
class TieuDeBaoMatFilter implements FilterInterface
{
    private const TIEU_DE = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'X-Content-Type-Options'    => 'nosniff',
        'X-Frame-Options'           => 'DENY',
        'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        'Content-Security-Policy'   => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
            . "connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        foreach (self::TIEU_DE as $ten => $giaTri) {
            $response->setHeader($ten, $giaTri);
        }

        return $response;
    }
}
