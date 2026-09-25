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
}
