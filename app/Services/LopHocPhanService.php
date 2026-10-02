<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\LopHocPhanRepository;
use App\Repositories\NhatKyHeThongRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - lop hoc phan (thu ky khoa cau hinh cung voi moc, F3.1).
 * Ma lop khong trung; lop con de tai hoac moc thi khong xoa.
 */
class LopHocPhanService
{
    public function __construct(
        protected LopHocPhanRepository $repo,
        protected NhatKyHeThongRepository $nhatKyRepo
    ) {
    }

    public function danhSach(): array
    {
        return $this->repo->findTatCa();
    }

    public function xem(int $id): array
    {
        return $this->layLop($id);
    }

    /** $input da duoc Controller kiem tra dinh dang: ma_lop, ten_lop, hoc_ky, nam_hoc */
    public function tao(array $input, array $currentUser): array
    {
        if ($this->repo->existsMaLop($input['ma_lop'])) {
            throw new ValidationException([['field' => 'ma_lop', 'issue' => 'Ma lop da ton tai']]);
        }

        $this->repo->transBegin();
        try {
            $id = $this->repo->create($input);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'CREATE_CLASS', 'Tao lop hoc phan ID: ' . $id . ' (' . $input['ma_lop'] . ')', $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    public function capNhat(int $id, array $input, array $currentUser): array
    {
        $this->layLop($id);

        if (isset($input['ma_lop']) && $this->repo->existsMaLop($input['ma_lop'], $id)) {
            throw new ValidationException([['field' => 'ma_lop', 'issue' => 'Ma lop da ton tai']]);
        }

        $this->repo->transBegin();
        try {
            $this->repo->capNhat($id, $input);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'UPDATE_CLASS', 'Cap nhat lop hoc phan ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    public function xoa(int $id, array $currentUser): void
    {
        $this->repo->transBegin();
        try {
            if (!$this->repo->findByIdForUpdate($id)) {
                throw new NotFoundException('Khong tim thay lop hoc phan');
            }

            $dem = $this->repo->demDuLieuLienQuan($id);
            if ($dem['de_tai'] > 0 || $dem['moc'] > 0) {
                throw new ApiException(409, 'CONFLICT', 'Lop con ' . $dem['de_tai'] . ' de tai va ' . $dem['moc'] . ' moc, khong xoa duoc');
            }

            $this->repo->delete($id);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'DELETE_CLASS', 'Xoa lop hoc phan ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }
    }

    private function layLop(int $id): array
    {
        $lop = $this->repo->findById($id);
        if (!$lop) {
            throw new NotFoundException('Khong tim thay lop hoc phan');
        }

        return $lop;
    }
}
