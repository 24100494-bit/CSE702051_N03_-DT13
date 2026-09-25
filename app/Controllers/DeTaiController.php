<?php

namespace App\Controllers;

use App\Services\DeTaiService;
use App\Repositories\DeTaiRepository;
use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller)
 * CHI duoc: doc tham so, kiem tra dinh dang dau vao, goi Service, chon ma HTTP.
 * KHONG duoc: truy van CSDL (khong duoc co chu SELECT/INSERT/UPDATE/DELETE o file nay),
 * khong duoc chua quy tac nghiep vu.
 */
class DeTaiController extends BaseController
{
    /** GET /api/v1/de-tai/{id} */
    public function show($id = null)
    {
        try {
            $id = (int) $id;

            if ($id <= 0) {
                throw new ValidationException(
                    [['field' => 'id', 'issue' => 'Phai la so nguyen duong']]
                );
            }

            $repo    = new DeTaiRepository(Database::connect());
            $service = new DeTaiService($repo);

            // TODO (viec cua V4): thay bang nguoi dung that lay tu session/JWT sau khi co xac thuc
            $currentUser = ['id' => 1, 'vai_tro' => 'sinh_vien'];

            $deTai = $service->getForUser($id, $currentUser);

            return $this->respondSuccess($deTai);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai?page=&size=&trang_thai= */
    public function index()
    {
        try {
            $page = (int) ($this->request->getGet('page') ?? 1);
            $size = (int) ($this->request->getGet('size') ?? 20);
            $trangThai = $this->request->getGet('trang_thai');

            if ($size > 100) {
                $size = 100; // chan client tu dat size qua lon lam sap he thong
            }

            $repo    = new DeTaiRepository(Database::connect());
            $service = new DeTaiService($repo);
            $currentUser = ['id' => 1, 'vai_tro' => 'sinh_vien'];

            $ketQua = $service->listForUser($page, $size, $trangThai, $currentUser);

            return $this->respondSuccess($ketQua);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }
}
