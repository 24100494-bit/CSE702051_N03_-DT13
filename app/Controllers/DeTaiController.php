<?php

namespace App\Controllers;

use App\Services\DeTaiService;
use App\Repositories\DeTaiRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
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
            $deTai = $this->service()->getForUser($this->idHopLe($id), $this->nguoiDungHienTai());

            return $this->respondSuccess($deTai);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai/{id}/lich-su - YCCN-12 */
    public function lichSu($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->lichSu($this->idHopLe($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai?page=&size=&trang_thai= */
    public function index()
    {
        try {
            $page = max(1, (int) ($this->request->getGet('page') ?? 1));
            $size = (int) ($this->request->getGet('size') ?? 20);
            $trangThai = $this->request->getGet('trang_thai') ?: null;

            if ($size < 1) {
                $size = 20;
            }
            if ($size > 100) {
                $size = 100; // chan client tu dat size qua lon lam sap he thong
            }

            $ketQua = $this->service()->listForUser($page, $size, $trangThai, $this->nguoiDungHienTai());

            return $this->respondSuccess($ketQua);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/de-tai - UC01 de xuat de tai */
    public function create()
    {
        try {
            $input = $this->docThan();
            $loi   = [];

            $ten   = trim((string) ($input['ten_de_tai'] ?? ''));
            $moTa  = trim((string) ($input['mo_ta_pham_vi'] ?? ''));
            $lopId = (int) ($input['lop_hoc_phan_id'] ?? 0);

            if ($ten === '' || mb_strlen($ten) > 200) {
                $loi[] = ['field' => 'ten_de_tai', 'issue' => 'Bat buoc, toi da 200 ky tu'];
            }
            if ($moTa === '') {
                $loi[] = ['field' => 'mo_ta_pham_vi', 'issue' => 'Bat buoc'];
            }
            if ($lopId <= 0) {
                $loi[] = ['field' => 'lop_hoc_phan_id', 'issue' => 'Bat buoc, la so nguyen duong'];
            }
            if ($loi) {
                throw new ValidationException($loi);
            }

            $deTai = $this->service()->deXuat(
                ['ten_de_tai' => $ten, 'mo_ta_pham_vi' => $moTa, 'lop_hoc_phan_id' => $lopId],
                $this->nguoiDungHienTai()
            );

            return $this->respondSuccess($deTai, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/de-tai/{id} - sua ten, mo ta khi con pending hoac bi tu choi */
    public function update($id = null)
    {
        try {
            $id    = $this->idHopLe($id);
            $input = $this->docThan();
            $data  = [];
            $loi   = [];

            if (array_key_exists('ten_de_tai', $input)) {
                $ten = trim((string) $input['ten_de_tai']);
                if ($ten === '' || mb_strlen($ten) > 200) {
                    $loi[] = ['field' => 'ten_de_tai', 'issue' => 'Khong duoc rong, toi da 200 ky tu'];
                }
                $data['ten_de_tai'] = $ten;
            }
            if (array_key_exists('mo_ta_pham_vi', $input)) {
                $moTa = trim((string) $input['mo_ta_pham_vi']);
                if ($moTa === '') {
                    $loi[] = ['field' => 'mo_ta_pham_vi', 'issue' => 'Khong duoc rong'];
                }
                $data['mo_ta_pham_vi'] = $moTa;
            }
            if (!$data && !$loi) {
                $loi[] = ['field' => 'ten_de_tai, mo_ta_pham_vi', 'issue' => 'Can gui it nhat mot truong de sua'];
            }
            if ($loi) {
                throw new ValidationException($loi);
            }

            return $this->respondSuccess($this->service()->capNhat($id, $data, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** DELETE /api/v1/de-tai/{id} - xoa de xuat chua duyet */
    public function delete($id = null)
    {
        try {
            $this->service()->xoa($this->idHopLe($id), $this->nguoiDungHienTai());

            return $this->response->setStatusCode(204);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/de-tai/{id}/duyet - UC02 */
    public function duyet($id = null)
    {
        try {
            $id      = $this->idHopLe($id);
            $nhanXet = trim((string) ($this->docThan()['nhan_xet'] ?? ''));

            $deTai = $this->service()->duyet($id, $nhanXet === '' ? null : $nhanXet, $this->nguoiDungHienTai());

            return $this->respondSuccess($deTai);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/de-tai/{id}/tu-choi - UC02 luong thay the, bat buoc ly do */
    public function tuChoi($id = null)
    {
        try {
            $id   = $this->idHopLe($id);
            $lyDo = trim((string) ($this->docThan()['ly_do'] ?? ''));

            if ($lyDo === '') {
                throw new ValidationException([['field' => 'ly_do', 'issue' => 'Bat buoc nhap ly do tu choi']]);
            }

            return $this->respondSuccess($this->service()->tuChoi($id, $lyDo, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): DeTaiService
    {
        $db = Database::connect();

        return new DeTaiService(
            new DeTaiRepository($db),
            new ThanhVienNhomRepository($db),
            new NhatKyHeThongRepository($db),
            new ThongBaoRepository($db)
        );
    }

    private function idHopLe($id): int
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new ValidationException([['field' => 'id', 'issue' => 'Phai la so nguyen duong']]);
        }

        return $id;
    }

    /** Nhan ca JSON lan form; JSON hong tra 422 thay vi de loi 500 */
    private function docThan(): array
    {
        try {
            $json = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            throw new ValidationException([['field' => 'body', 'issue' => 'Than yeu cau khong phai JSON hop le (UTF-8)']]);
        }

        return is_array($json) ? $json : (array) $this->request->getPost();
    }
}
