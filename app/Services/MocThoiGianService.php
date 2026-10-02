<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\LopHocPhanRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NhatKyHeThongRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - moc thoi gian chung cua lop hoc phan (F3.1 - F3.3).
 * Ghi moc + nhat ky trong mot giao dich. Moc da co ban nop thi khong xoa.
 */
class MocThoiGianService
{
    public function __construct(
        protected MocThoiGianRepository $repo,
        protected LopHocPhanRepository $lopRepo,
        protected NhatKyHeThongRepository $nhatKyRepo
    ) {
    }

    /** F3.2 - danh sach moc cua mot lop, sap theo han nop */
    public function danhSach(int $lopHocPhanId): array
    {
        if (!$this->lopRepo->findById($lopHocPhanId)) {
            throw new NotFoundException('Khong tim thay lop hoc phan');
        }

        return $this->repo->findByLopHocPhan($lopHocPhanId);
    }

    /** F3.1 - tao moc; $input da duoc Controller kiem tra dinh dang */
    public function tao(array $input, array $currentUser): array
    {
        $this->repo->transBegin();
        try {
            if (!$this->lopRepo->findByIdForUpdate((int) $input['lop_hoc_phan_id'])) {
                throw new ValidationException([['field' => 'lop_hoc_phan_id', 'issue' => 'Lop hoc phan khong ton tai']]);
            }

            $id = $this->repo->create($input);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'CREATE_MILESTONE', 'Tao moc ID: ' . $id . ' cho lop ID: ' . $input['lop_hoc_phan_id'], $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    /** F3.3 - cap nhat ten, mo ta, han nop, bat buoc */
    public function capNhat(int $id, array $input, array $currentUser): array
    {
        $this->layMoc($id);

        $this->repo->transBegin();
        try {
            $this->repo->capNhat($id, $input);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'UPDATE_MILESTONE', 'Cap nhat moc ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    /** F3.1 - xoa moc chua co ban nop nao */
    public function xoa(int $id, array $currentUser): void
    {
        $this->layMoc($id);

        if ($this->repo->demBaoCao($id) > 0) {
            throw new ApiException(409, 'CONFLICT', 'Moc da co bao cao nop, khong xoa duoc');
        }

        $this->repo->transBegin();
        try {
            $this->repo->delete($id);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'DELETE_MILESTONE', 'Xoa moc ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }
    }

    private function layMoc(int $id): array
    {
        $moc = $this->repo->findById($id);
        if (!$moc) {
            throw new NotFoundException('Khong tim thay moc');
        }

        return $moc;
    }
}
