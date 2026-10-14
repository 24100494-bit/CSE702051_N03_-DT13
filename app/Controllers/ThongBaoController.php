<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Repositories\NhacHanRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\NhacHanService;
use App\Services\ThongBaoService;
use Config\Database;
use Throwable;

/**
 * TANG DIEU KHIEN (Controller) - thong bao cua toi (F6.1, F6.2).
 */
class ThongBaoController extends BaseController
{
    /** GET /api/v1/thong-bao?page=&size=&chua_doc=1 */
    public function index()
    {
        try {
            [$page, $size] = $this->layPhanTrang();
            $chiChuaDoc    = filter_var($this->request->getGet('chua_doc') ?? false, FILTER_VALIDATE_BOOLEAN);
            $nguoiDung     = $this->nguoiDungHienTai();

            $this->nhacHanChoSinhVien($nguoiDung);

            return $this->respondSuccess($this->service()->cuaToi($page, $size, $chiChuaDoc, $nguoiDung));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/thong-bao/{id}/da-doc */
    public function daDoc($id = null)
    {
        try {
            return $this->respondSuccess($this->service()->danhDauDaDoc($this->layId($id), $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /**
     * K9: may chu khong co lich chay dinh ky nen nhac han khi sinh vien mo thong bao.
     * Loi khi nhac han chi ghi log, khong lam hong danh sach thong bao.
     */
    private function nhacHanChoSinhVien(array $nguoiDung): void
    {
        $vaiTro = array_map(static fn ($v) => strtoupper((string) $v), $nguoiDung['danh_sach_vai_tro'] ?? []);
        if (!in_array('SINH_VIEN', $vaiTro, true)) {
            return;
        }

        try {
            $db = Database::connect();
            (new NhacHanService(new NhacHanRepository($db), new ThongBaoRepository($db)))->choSinhVien((int) $nguoiDung['id']);
        } catch (Throwable $e) {
            log_message('error', 'Nhac han that bai: ' . $e->getMessage());
        }
    }

    private function service(): ThongBaoService
    {
        return new ThongBaoService(new ThongBaoRepository(Database::connect()));
    }
}
