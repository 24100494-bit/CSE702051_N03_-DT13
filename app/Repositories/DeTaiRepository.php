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
