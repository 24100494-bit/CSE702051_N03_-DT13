<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - quan ly tai khoan cua thu ky khoa (F1.6):
 * danh sach, xem, tao tai khoan theo vai tro, khoa / mo, doi vai tro.
 * Tai khoan khong xoa (con lien ket de tai, nhat ky); thay vao do la khoa.
 */
class NguoiDungService
{
    public const VAI_TRO_HOP_LE = ['SINH_VIEN', 'GVHD', 'THU_KY_KHOA', 'HOI_DONG'];

    public function __construct(
        protected NguoiDungRepository $repo,
        protected NhatKyHeThongRepository $nhatKyRepo
    ) {
    }

    public function danhSach(int $page, int $size, ?string $vaiTro, ?string $tuKhoa): array
    {
        $ketQua = $this->repo->findTheoBoLoc($page, $size, $vaiTro, $tuKhoa);

        $ketQua['items'] = array_map(static function (array $nd): array {
            $nd['id']      = (int) $nd['id'];
            $nd['bi_khoa'] = (int) $nd['bi_khoa'] === 1;
            $nd['vai_tro'] = $nd['vai_tro'] === null ? [] : explode(',', $nd['vai_tro']);

            return $nd;
        }, $ketQua['items']);

        return $ketQua;
    }

    public function xem(int $id): array
    {
        $nguoiDung = $this->repo->findById($id);
        if (!$nguoiDung) {
            throw new NotFoundException('Khong tim thay nguoi dung');
        }

        return $this->dinhDang($nguoiDung);
    }

    /** Thu ky tao tai khoan voi vai tro chi dinh (vi du tai khoan GVHD, hoi dong); $input da kiem tra dinh dang */
    public function tao(array $input, array $currentUser): array
    {
        $trung = [];
        if ($this->repo->existsTenDangNhap($input['ten_dang_nhap'])) {
            $trung[] = ['field' => 'ten_dang_nhap', 'issue' => 'Ten dang nhap da duoc su dung'];
        }
        if ($this->repo->existsEmail($input['email'])) {
            $trung[] = ['field' => 'email', 'issue' => 'Email da duoc su dung'];
        }
        if ($trung) {
            throw new ValidationException($trung);
        }

        $this->repo->transBegin();
        try {
            $id = $this->repo->create([
                'ten_dang_nhap' => $input['ten_dang_nhap'],
                'mat_khau_hash' => password_hash($input['mat_khau'], PASSWORD_BCRYPT, ['cost' => XacThucService::BCRYPT_COST]),
                'ho_ten'        => $input['ho_ten'],
                'email'         => $input['email'],
                'so_dien_thoai' => $input['so_dien_thoai'] ?? null,
            ]);
            $this->repo->ganVaiTro($id, $input['vai_tro']);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'CREATE_ACCOUNT', 'Tao tai khoan ID: ' . $id . ' vai tro ' . $input['vai_tro'], $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->xem($id);
    }

    /**
     * Khoa / mo tai khoan va / hoac doi vai tro. Thu ky khong tu khoa minh, khong tu bo vai tro thu ky cua minh
     * (tranh he thong mat het nguoi quan tri).
     */
    public function capNhatTrangThai(int $id, ?bool $khoa, ?string $vaiTro, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        if ($id === $userId && ($khoa === true || ($vaiTro !== null && $vaiTro !== 'THU_KY_KHOA'))) {
            throw new ApiException(422, 'INVALID_TRANSITION', 'Khong tu khoa hoac tu bo vai tro thu ky cua chinh minh');
        }

        $this->repo->transBegin();
        try {
            $nguoiDung = $this->repo->findByIdForUpdate($id);
            if (!$nguoiDung) {
                throw new NotFoundException('Khong tim thay nguoi dung');
            }

            if ($khoa !== null && (int) $nguoiDung['bi_khoa'] !== ($khoa ? 1 : 0)) {
                $this->repo->datKhoa($id, $khoa);
                $this->nhatKyRepo->ghiNhan($userId, $khoa ? 'LOCK_ACCOUNT' : 'UNLOCK_ACCOUNT', ($khoa ? 'Khoa' : 'Mo') . ' tai khoan ID: ' . $id, $currentUser['ip'] ?? null);
            }
            if ($vaiTro !== null) {
                $cu = $this->repo->getVaiTro($id);
                if ($cu !== [$vaiTro]) {
                    $this->repo->thayVaiTro($id, $vaiTro);
                    $this->nhatKyRepo->ghiNhan($userId, 'CHANGE_ROLE', 'Doi vai tro tai khoan ID: ' . $id . ' tu ' . implode(',', $cu) . ' sang ' . $vaiTro, $currentUser['ip'] ?? null);
                }
            }

            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->xem($id);
    }

    private function dinhDang(array $nguoiDung): array
    {
        $nguoiDung['id']      = (int) $nguoiDung['id'];
        $nguoiDung['bi_khoa'] = (int) $nguoiDung['bi_khoa'] === 1;
        $nguoiDung['vai_tro'] = $this->repo->getVaiTro($nguoiDung['id']);

        return $nguoiDung;
    }
}
