<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\DeTaiRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - nhom sinh vien thuc hien de tai.
 * Nhom gan voi de tai: ma nhom chinh la ma de tai, nhom truong la sinh vien de xuat (thanh_vien_nhom = leader).
 * Gom: xem nhom (F2.10), phan cong GVHD (F2.8, UC01 buoc 4), them / xoa thanh vien (F2.11).
 */
class NhomSinhVienService
{
    /** Trang thai de tai con duoc doi thanh vien nhom */
    private const DE_TAI_DOI_THANH_VIEN = ['pending', 'rejected', 'approved', 'in_progress'];

    public function __construct(
        protected DeTaiRepository $deTaiRepo,
        protected ThanhVienNhomRepository $thanhVienRepo,
        protected NguoiDungRepository $nguoiDungRepo,
        protected NhatKyHeThongRepository $nhatKyRepo,
        protected ThongBaoRepository $thongBaoRepo
    ) {
    }

    /** Thong tin nhom: de tai, GVHD, danh sach thanh vien. Thanh vien nhom va thu ky khoa duoc xem */
    public function xem(int $deTaiId, array $currentUser): array
    {
        $deTai  = $this->layDeTai($deTaiId);
        $userId = (int) $currentUser['id'];

        $laThanhVien = $this->coVaiTro($currentUser, 'SINH_VIEN')
            && ((int) $deTai['sinh_vien_de_xuat_id'] === $userId || $this->thanhVienRepo->laThanhVien($deTaiId, $userId));

        if (!$laThanhVien && !$this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
            throw new ForbiddenException('Ban khong co quyen xem nhom nay');
        }

        $gvhd = $deTai['gvhd_id'] ? $this->nguoiDungRepo->findById((int) $deTai['gvhd_id']) : null;

        return [
            'de_tai_id'   => (int) $deTai['id'],
            'ten_de_tai'  => $deTai['ten_de_tai'],
            'trang_thai'  => $deTai['trang_thai'],
            'gvhd'        => $gvhd ? ['id' => (int) $gvhd['id'], 'ho_ten' => $gvhd['ho_ten'], 'email' => $gvhd['email']] : null,
            'thanh_vien'  => $this->thanhVienRepo->findByDeTai($deTaiId),
        ];
    }

    /**
     * F2.8 - thu ky khoa phan cong GVHD cho nhom khi de tai con cho duyet; GVHD nhan thong bao (UC01 buoc 4).
     * Doi gvhd_id + nhat ky + hai thong bao trong mot giao dich, dong de tai duoc khoa.
     */
    public function phanCongGvhd(int $deTaiId, int $gvhdId, array $currentUser): array
    {
        $gvhd = $this->nguoiDungRepo->findById($gvhdId);
        if (!$gvhd || !in_array('GVHD', $this->nguoiDungRepo->getVaiTro($gvhdId), true)) {
            throw new ValidationException([['field' => 'gvhd_id', 'issue' => 'Khong phai tai khoan giang vien huong dan']]);
        }
        if ((int) $gvhd['bi_khoa'] === 1) {
            throw new ValidationException([['field' => 'gvhd_id', 'issue' => 'Tai khoan giang vien dang bi khoa']]);
        }

        $userId = (int) $currentUser['id'];

        $this->deTaiRepo->transBegin();
        try {
            $deTai = $this->deTaiRepo->findByIdForUpdate($deTaiId);
            if (!$deTai) {
                throw new NotFoundException('Khong tim thay de tai');
            }
            if ($deTai['trang_thai'] !== 'pending') {
                throw new ApiException(422, 'INVALID_TRANSITION', 'Chi phan cong GVHD khi de tai dang cho duyet');
            }

            $this->deTaiRepo->ganGvhd($deTaiId, $gvhdId);
            $this->nhatKyRepo->ghiNhan($userId, 'ASSIGN_SUPERVISOR', 'Phan cong GVHD ID: ' . $gvhdId . ' cho de tai ID: ' . $deTaiId, $currentUser['ip'] ?? null);
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => $gvhdId,
                'tieu_de'       => 'Có đề tài chờ duyệt',
                'noi_dung'      => 'Bạn được phân công hướng dẫn đề tài ID ' . $deTaiId . ': ' . $deTai['ten_de_tai'] . '.',
            ]);
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => (int) $deTai['sinh_vien_de_xuat_id'],
                'tieu_de'       => 'Đã phân công giảng viên hướng dẫn',
                'noi_dung'      => 'Đề tài ID ' . $deTaiId . ' được phân công cho ' . $gvhd['ho_ten'] . '.',
            ]);

            $this->deTaiRepo->transCommit();
        } catch (Throwable $e) {
            $this->deTaiRepo->transRollback();
            throw $e;
        }

        return $this->deTaiRepo->findById($deTaiId);
    }

    /** F2.11 - nhom truong hoac thu ky them sinh vien vao nhom (vai tro member) */
    public function themThanhVien(int $deTaiId, int $sinhVienId, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        $this->deTaiRepo->transBegin();
        try {
            $deTai = $this->layDeTaiDeDoiThanhVien($deTaiId, $currentUser);

            $sinhVien = $this->nguoiDungRepo->findByIdForUpdate($sinhVienId);
            if (!$sinhVien || !in_array('SINH_VIEN', $this->nguoiDungRepo->getVaiTro($sinhVienId), true)) {
                throw new ValidationException([['field' => 'sinh_vien_id', 'issue' => 'Khong phai tai khoan sinh vien']]);
            }
            if ((int) $sinhVien['bi_khoa'] === 1) {
                throw new ValidationException([['field' => 'sinh_vien_id', 'issue' => 'Tai khoan sinh vien dang bi khoa']]);
            }
            if ($this->thanhVienRepo->laThanhVien($deTaiId, $sinhVienId)) {
                throw new ApiException(409, 'CONFLICT', 'Sinh vien da la thanh vien cua nhom');
            }
            if ($this->deTaiRepo->coDeTaiDangHoatDong($sinhVienId)) {
                throw new ApiException(422, 'DA_CO_DE_TAI', 'Sinh vien dang thuoc nhom cua de tai khac');
            }

            $this->thanhVienRepo->themThanhVien($deTaiId, $sinhVienId, 'member');
            $this->nhatKyRepo->ghiNhan($userId, 'ADD_MEMBER', 'Them sinh vien ID: ' . $sinhVienId . ' vao nhom de tai ID: ' . $deTaiId, $currentUser['ip'] ?? null);
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => $sinhVienId,
                'tieu_de'       => 'Bạn được thêm vào nhóm',
                'noi_dung'      => 'Bạn đã được thêm vào nhóm thực hiện đề tài ID ' . $deTaiId . ': ' . $deTai['ten_de_tai'] . '.',
            ]);

            $this->deTaiRepo->transCommit();
        } catch (Throwable $e) {
            $this->deTaiRepo->transRollback();
            throw $e;
        }

        return $this->thanhVienRepo->findByDeTai($deTaiId);
    }

    /** F2.11 - nhom truong hoac thu ky xoa thanh vien; khong xoa nhom truong */
    public function xoaThanhVien(int $deTaiId, int $sinhVienId, array $currentUser): void
    {
        $userId = (int) $currentUser['id'];

        $this->deTaiRepo->transBegin();
        try {
            $this->layDeTaiDeDoiThanhVien($deTaiId, $currentUser);

            $vaiTroNhom = $this->thanhVienRepo->findVaiTroNhom($deTaiId, $sinhVienId);
            if ($vaiTroNhom === null) {
                throw new NotFoundException('Sinh vien khong thuoc nhom nay');
            }
            if ($vaiTroNhom === 'leader') {
                throw new ApiException(422, 'INVALID_TRANSITION', 'Khong xoa nhom truong khoi nhom');
            }

            $this->thanhVienRepo->xoaThanhVien($deTaiId, $sinhVienId);
            $this->nhatKyRepo->ghiNhan($userId, 'REMOVE_MEMBER', 'Xoa sinh vien ID: ' . $sinhVienId . ' khoi nhom de tai ID: ' . $deTaiId, $currentUser['ip'] ?? null);
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => $sinhVienId,
                'tieu_de'       => 'Bạn đã bị xóa khỏi nhóm',
                'noi_dung'      => 'Bạn không còn là thành viên nhóm của đề tài ID ' . $deTaiId . '.',
            ]);

            $this->deTaiRepo->transCommit();
        } catch (Throwable $e) {
            $this->deTaiRepo->transRollback();
            throw $e;
        }
    }

    /** Trong giao dich: khoa de tai, kiem nguoi goi la nhom truong hoac thu ky, de tai con doi duoc thanh vien */
    private function layDeTaiDeDoiThanhVien(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->deTaiRepo->findByIdForUpdate($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $laNhomTruong = $this->coVaiTro($currentUser, 'SINH_VIEN')
            && (int) $deTai['sinh_vien_de_xuat_id'] === (int) $currentUser['id'];

        if (!$laNhomTruong && !$this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
            throw new ForbiddenException('Chi nhom truong hoac thu ky khoa duoc doi thanh vien nhom');
        }
        if (!in_array($deTai['trang_thai'], self::DE_TAI_DOI_THANH_VIEN, true)) {
            throw new ApiException(422, 'INVALID_TRANSITION', 'De tai da hoan thanh, khong doi thanh vien');
        }

        return $deTai;
    }

    private function layDeTai(int $deTaiId): array
    {
        $deTai = $this->deTaiRepo->findById($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        return $deTai;
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
