<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\BaoCaoMocService;
use Config\Database;

/**
 * TANG DIEU KHIEN (Controller) - Luong 2: nop va duyet bao cao theo moc
 * CHI doc tham so, kiem tra dinh dang, goi Service, chon ma HTTP; khong truy van CSDL.
 */
class BaoCaoMocController extends BaseController
{
    /** POST /api/v1/de-tai/{deTaiId}/bao-cao-moc (multipart: moc_thoi_gian_id, tep) - UC03 */
    public function create($deTaiId = null)
    {
        try {
            $deTaiId = $this->idHopLe($deTaiId, 'deTaiId');
            $mocId   = (int) ($this->request->getPost('moc_thoi_gian_id') ?? 0);

            if ($mocId <= 0) {
                throw new ValidationException([['field' => 'moc_thoi_gian_id', 'issue' => 'Bat buoc, la so nguyen duong']]);
            }

            $baoCao = $this->service()->nop($deTaiId, $mocId, $this->request->getFile('tep'), $this->nguoiDungHienTai());

            return $this->respondSuccess($baoCao, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/de-tai/{deTaiId}/bao-cao-moc - cac ban nop cua de tai, moi nhat truoc */
    public function index($deTaiId = null)
    {
        try {
            $deTaiId = $this->idHopLe($deTaiId, 'deTaiId');

            return $this->respondSuccess($this->service()->danhSachTheoDeTai($deTaiId, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** GET /api/v1/bao-cao-moc/{id} */
    public function show($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->xem($this->idHopLe($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /**
     * POST /api/v1/bao-cao-moc/{id} (multipart: tep) - nop lai khi bi yeu cau bo sung, tao ban ghi moi.
     * Dung POST vi PHP khong doc duoc tep gui bang PUT multipart.
     */
    public function nopLai($id = null)
    {
        try {
            $baoCao = $this->service()->nopLai($this->idHopLe($id), $this->request->getFile('tep'), $this->nguoiDungHienTai());

            return $this->respondSuccess($baoCao, 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/bao-cao-moc/{id}/duyet - UC04 */
    public function duyet($id = null)
    {
        try {
            $id      = $this->idHopLe($id);
            $nhanXet = trim((string) ($this->docThan()['nhan_xet_gv'] ?? ''));

            $baoCao = $this->service()->duyet($id, $nhanXet === '' ? null : $nhanXet, $this->nguoiDungHienTai());

            return $this->respondSuccess($baoCao);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/bao-cao-moc/{id}/yeu-cau-bo-sung - UC04 luong thay the, bat buoc nhan xet */
    public function yeuCauBoSung($id = null)
    {
        try {
            $id      = $this->idHopLe($id);
            $nhanXet = trim((string) ($this->docThan()['nhan_xet_gv'] ?? ''));

            if ($nhanXet === '') {
                throw new ValidationException([['field' => 'nhan_xet_gv', 'issue' => 'Bat buoc nhap nhan xet']]);
            }

            return $this->respondSuccess($this->service()->yeuCauBoSung($id, $nhanXet, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    private function service(): BaoCaoMocService
    {
        $db = Database::connect();

        return new BaoCaoMocService(
            new BaoCaoMocRepository($db),
            new DeTaiRepository($db),
            new MocThoiGianRepository($db),
            new ThanhVienNhomRepository($db),
            new NhatKyHeThongRepository($db),
            new ThongBaoRepository($db),
            WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bao_cao'
        );
    }

    private function idHopLe($id, string $truong = 'id'): int
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new ValidationException([['field' => $truong, 'issue' => 'Phai la so nguyen duong']]);
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
