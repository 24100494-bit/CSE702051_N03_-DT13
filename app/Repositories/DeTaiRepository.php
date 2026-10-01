<?php

namespace App\Repositories;

/**
 * TANG TRUY CAP DU LIEU (Repository) - bang `de_tai`, thuc the trung tam cua he thong
 * CHI duoc: truy van tham so hoa, tra ve mang du lieu tho.
 * KHONG duoc: chua dieu kien nghiep vu, kiem tra quyen.
 * findById() ke thua tu BaseRepository.
 */
class DeTaiRepository extends BaseRepository
{
    protected string $table = 'de_tai';

    protected array $allowedFields = ['ten_de_tai', 'mo_ta_pham_vi', 'trang_thai', 'sinh_vien_de_xuat_id', 'gvhd_id', 'lop_hoc_phan_id'];

    public function findAllPaginated(int $page, int $size, ?string $trangThai = null): array
    {
        $builder = $this->db->table('de_tai');

        if ($trangThai !== null) {
            $builder->where('trang_thai', $trangThai);
        }

        $total = $builder->countAllResults(false);

        $rows = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'size' => $size];
    }

    /** Danh sach de tai theo lop hoc phan, loc theo trang thai (tuy chon) */
    public function findByLopHocPhan(int $lopHocPhanId, ?string $trangThai = null): array
    {
        $builder = $this->db->table('de_tai')->where('lop_hoc_phan_id', $lopHocPhanId);

        if ($trangThai !== null) {
            $builder->where('trang_thai', $trangThai);
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /** De tai ma mot GVHD dang huong dan */
    public function findByGvhd(int $gvhdId): array
    {
        return $this->db->table('de_tai')
            ->where('gvhd_id', $gvhdId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /** De tai ma mot sinh vien tham gia, qua bang trung gian thanh_vien_nhom (dung cho kiem quyen tren doi tuong) */
    public function findByThanhVien(int $sinhVienId): array
    {
        return $this->db->table('de_tai dt')
            ->select('dt.*')
            ->join('thanh_vien_nhom tvn', 'tvn.de_tai_id = dt.id')
            ->where('tvn.sinh_vien_id', $sinhVienId)
            ->orderBy('dt.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Danh sach co phan trang, gioi han theo pham vi cua nguoi goi:
     * - $sinhVienId: chi de tai sinh vien do de xuat hoac la thanh vien nhom
     * - $gvhdId: chi de tai GVHD do phu trach
     * - $chiTrangThai: chi cac trang thai trong danh sach (vi du Hoi dong chi thay de tai completed)
     */
    public function findTheoPhamVi(int $page, int $size, ?string $trangThai, ?int $sinhVienId = null, ?int $gvhdId = null, ?array $chiTrangThai = null): array
    {
        $builder = $this->db->table('de_tai');

        if ($sinhVienId !== null) {
            $builder->groupStart()
                ->where('sinh_vien_de_xuat_id', $sinhVienId)
                ->orWhereIn('id', static fn ($sub) => $sub->select('de_tai_id')->from('thanh_vien_nhom')->where('sinh_vien_id', $sinhVienId))
                ->groupEnd();
        }
        if ($gvhdId !== null) {
            $builder->where('gvhd_id', $gvhdId);
        }
        if ($chiTrangThai !== null) {
            $builder->whereIn('trang_thai', $chiTrangThai);
        }
        if ($trangThai !== null) {
            $builder->where('trang_thai', $trangThai);
        }

        $total = $builder->countAllResults(false);

        $rows = $builder
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'size' => $size];
    }

    /** Doc va khoa dong de tai trong giao dich, de hai thao tac dong thoi khong cung chuyen trang thai */
    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->db->query('SELECT * FROM de_tai WHERE id = ? FOR UPDATE', [$id])->getRowArray();

        return $row ?: null;
    }

    /** Trung ten de tai trong cung lop (so sanh khong phan biet hoa thuong theo collation cua bang) */
    public function existsTenTrongLop(int $lopHocPhanId, string $tenDeTai, ?int $boQuaId = null): bool
    {
        $builder = $this->db->table('de_tai')
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->where('ten_de_tai', $tenDeTai);

        if ($boQuaId !== null) {
            $builder->where('id !=', $boQuaId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Sinh vien dang co de tai chua bi tu choi (pending, approved, in_progress, completed) */
    public function coDeTaiDangHoatDong(int $sinhVienId): bool
    {
        return $this->db->table('de_tai')
            ->groupStart()
                ->where('sinh_vien_de_xuat_id', $sinhVienId)
                ->orWhereIn('id', static fn ($sub) => $sub->select('de_tai_id')->from('thanh_vien_nhom')->where('sinh_vien_id', $sinhVienId))
            ->groupEnd()
            ->where('trang_thai !=', 'rejected')
            ->countAllResults() > 0;
    }

    public function capNhat(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function create(array $data): int
    {
        // $data: ten_de_tai, mo_ta_pham_vi, sinh_vien_de_xuat_id, lop_hoc_phan_id, [trang_thai, gvhd_id]
        $data['trang_thai'] ??= 'pending';

        return $this->insert($data);
    }

    public function capNhatTrangThai(int $id, string $trangThaiMoi): bool
    {
        return $this->update($id, ['trang_thai' => $trangThaiMoi]);
    }

    public function ganGvhd(int $id, int $gvhdId): bool
    {
        return $this->update($id, ['gvhd_id' => $gvhdId]);
    }
}
