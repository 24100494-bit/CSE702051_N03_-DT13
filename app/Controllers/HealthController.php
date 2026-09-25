<?php

namespace App\Controllers;

class HealthController extends BaseController
{
    /** GET /api/v1/health -- khong yeu cau xac thuc */
    public function index()
    {
        return $this->response->setStatusCode(200)->setJSON([
            'data' => [
                'status' => 'ok',
                'time'   => date('c'),
            ],
        ]);
    }
}
