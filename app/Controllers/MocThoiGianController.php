<?php

namespace App\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\ValidationException;
use App\Repositories\LopHocPhanRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\MocThoiGianService;
use Config\Database;
use DateTime;

/**
 * TANG DIEU KHIEN (Controller) - moc thoi gian chung cua lop hoc phan (F3.1 - F3.3).
 */
class MocThoiGianController extends BaseController
{
    /** GET /api/v1/moc-thoi-gian?lop_hoc_phan_id= - F3.2 */
    public function index()
    {
        try {
            $lopId = $this->layId($this->request->getGet('lop_hoc_phan_id'), 'lop_hoc_phan_id');

            return $this->respondSuccess($this->service()->danhSach($lopId));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** POST /api/v1/moc-thoi-gian - F3.1 */
    public function create()
    {
        try {
            $input = $this->layThanYeuCau();
            $data  = $this->kiemTraDuLieu($input, true);

            return $this->respondSuccess($this->service()->tao($data, $this->nguoiDungHienTai()), 201);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** PATCH /api/v1/moc-thoi-gian/{id} - F3.3 */
    public function update($id = null)
    {
        try {
            $id   = $this->layId($id);
            $data = $this->kiemTraDuLieu($this->layThanYeuCau(), false);

            return $this->respondSuccess($this->service()->capNhat($id, $data, $this->nguoiDungHienTai()));
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /** DELETE /api/v1/moc-thoi-gian/{id} - F3.1 */
    public function delete($id = null)
    {
        try {
            $this->service()->xoa($this->layId($id), $this->nguoiDungHienTai());

            return $this->response->setStatusCode(204);
        } catch (ApiException $e) {
            return $this->respondError($e);
        }
    }

    /**
     * Kiem tra dinh dang; $taoMoi = true thi lop_hoc_phan_id, ten_moc, han_nop la bat buoc.
     * han_nop nhan "Y-m-d H:i:s", "Y-m-d H:i" hoac "Y-m-dTH:i" (o nhap datetime-local), luu theo gio Viet Nam.
     */
    private function kiemTraDuLieu(array $input, bool $taoMoi): array
    {
        $data = [];
        $loi  = [];

        if ($taoMoi) {
            $lopId = (int) ($input['lop_hoc_phan_id'] ?? 0);
            if ($lopId <= 0) {
                $loi[] = ['field' => 'lop_hoc_phan_id', 'issue' => 'Bat buoc, la so nguyen duong'];
            }
            $data['lop_hoc_phan_id'] = $lopId;
        }
        if ($taoMoi || array_key_exists('ten_moc', $input)) {
            $ten = trim((string) ($input['ten_moc'] ?? ''));
            if ($ten === '' || mb_strlen($ten) > 100) {
                $loi[] = ['field' => 'ten_moc', 'issue' => 'Bat buoc, toi da 100 ky tu'];
            }
            $data['ten_moc'] = $ten;
        }
        if (array_key_exists('mo_ta', $input)) {
            $data['mo_ta'] = trim((string) $input['mo_ta']) ?: null;
        }
        if ($taoMoi || array_key_exists('han_nop', $input)) {
            $han = $this->docThoiDiem((string) ($input['han_nop'] ?? ''));
            if ($han === null) {
                $loi[] = ['field' => 'han_nop', 'issue' => 'Bat buoc, dang Y-m-d H:i:s'];
            }
            $data['han_nop'] = $han;
        }
        if (array_key_exists('bat_buoc', $input)) {
            $data['bat_buoc'] = filter_var($input['bat_buoc'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (!$data && !$loi) {
            $loi[] = ['field' => 'ten_moc, mo_ta, han_nop, bat_buoc', 'issue' => 'Can gui it nhat mot truong de sua'];
        }
        if ($loi) {
            throw new ValidationException($loi);
        }

        return $data;
    }

    private function docThoiDiem(string $giaTri): ?string
    {
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $dang) {
            $tg = DateTime::createFromFormat('!' . $dang, trim($giaTri));
            if ($tg && $tg->format($dang) === trim($giaTri)) {
                return $tg->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function service(): MocThoiGianService
    {
        $db = Database::connect();

        return new MocThoiGianService(new MocThoiGianRepository($db), new LopHocPhanRepository($db), new NhatKyHeThongRepository($db));
    }
}
