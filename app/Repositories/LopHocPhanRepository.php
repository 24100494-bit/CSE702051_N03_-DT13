<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `lop_hoc_phan`.
 */
class LopHocPhanRepository extends BaseRepository
{
    protected string $table = 'lop_hoc_phan';

    protected array $allowedFields = ['ma_lop', 'ten_lop', 'hoc_ky', 'nam_hoc'];

    public function findByMaLop(string $maLop): ?array
    {
        $row = $this->db->table('lop_hoc_phan')
            ->where('ma_lop', $maLop)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        // $data: ma_lop, ten_lop, hoc_ky, nam_hoc
        return $this->insert($data);
    }

    /** Danh sach lop, moi nhat truoc */
    public function findTatCa(): array
    {
        return $this->db->table('lop_hoc_phan')
            ->orderBy('nam_hoc', 'DESC')
            ->orderBy('hoc_ky', 'DESC')
            ->orderBy('ma_lop', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function existsMaLop(string $maLop, ?int $boQuaId = null): bool
    {
        $builder = $this->db->table('lop_hoc_phan')->where('ma_lop', $maLop);

        if ($boQuaId !== null) {
            $builder->where('id !=', $boQuaId);
        }

        return $builder->countAllResults() > 0;
    }

    /** Doc va khoa dong lop trong giao dich (tuan tu hoa cac thao tac ghi tren cung mot lop) */
    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->db->query('SELECT * FROM lop_hoc_phan WHERE id = ? FOR UPDATE', [$id])->getRowArray();

        return $row ?: null;
    }

    /** So de tai va so moc dang gan voi lop (lop con du lieu thi khong xoa) */
    public function demDuLieuLienQuan(int $id): array
    {
        return [
            'de_tai' => $this->db->table('de_tai')->where('lop_hoc_phan_id', $id)->countAllResults(),
            'moc'    => $this->db->table('moc_thoi_gian')->where('lop_hoc_phan_id', $id)->countAllResults(),
        ];
    }

    public function capNhat(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
