<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * File nay CodeIgniter da tu sinh san khi cai dat (composer create-project).
 * Ban CHI CAN THEM 2 ham respondSuccess() va respondError() vao cuoi class co san,
 * KHONG xoa nhung gi CodeIgniter da sinh ra ben tren.
 */
class BaseController extends Controller
{
    protected $helpers = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /** Tra ve phan hoi thanh cong dung dinh dang chung */
    protected function respondSuccess($data, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON(['data' => $data]);
    }

    /**
     * Tra ve phan hoi loi dung cau truc da chot trong QUYUOC_MA_NGUON.md.
     * Chi tiet loi that (that ra la gi) CHI ghi vao log, KHONG bao gio tra ve nguoi dung -- dung BM9.
     */
    protected function respondError(ApiException $e)
    {
        $requestId = bin2hex(random_bytes(8));

        log_message('error', '[' . $requestId . '] ' . $e->getMessage());

        return $this->response->setStatusCode($e->getStatusCode())->setJSON([
            'error' => [
                'code'      => $e->getErrorCode(),
                'message'   => $e->getMessage(),
                'details'   => $e->getDetails(),
                'requestId' => $requestId,
            ],
        ]);
    }
}
