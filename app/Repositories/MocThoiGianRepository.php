<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `moc_thoi_gian`.
 */
class MocThoiGianRepository extends BaseRepository
{
    protected string $table = 'moc_thoi_gian';

    protected array $allowedFields = ['lop_hoc_phan_id', 'ten_moc', 'mo_ta', 'han_nop', 'bat_buoc'];

    public function findByLopHocPhan(int $lopHocPhanId): array
    {
        return $this->db->table('moc_thoi_gian')
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->orderBy('han_nop', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Cac moc chua qua han cua mot lop - dung cho trang tong quan tien do */
    public function findSapToi(int $lopHocPhanId): array
    {
        return $this->db->table('moc_thoi_gian')
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->where('han_nop >= NOW()', null, false)
            ->orderBy('han_nop', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): int
    {
        // $data: lop_hoc_phan_id, ten_moc, han_nop, [mo_ta, bat_buoc]
        return $this->insert($data);
    }

    public function capNhat(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /** So ban nop da gan voi moc (moc da co ban nop thi khong xoa) */
    public function demBaoCao(int $mocId): int
    {
        return $this->db->table('bao_cao_moc')->where('moc_thoi_gian_id', $mocId)->countAllResults();
    }

    /** So moc bat buoc cua mot lop (dieu kien du nghiem thu) */
    public function demBatBuoc(int $lopHocPhanId): int
    {
        return $this->db->table('moc_thoi_gian')
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->where('bat_buoc', 1)
            ->countAllResults();
    }
}
