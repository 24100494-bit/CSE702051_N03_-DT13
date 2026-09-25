<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `diem_nghiem_thu`.
 */
class DiemNghiemThuRepository extends BaseRepository
{
    protected string $table = 'diem_nghiem_thu';

    protected array $allowedFields = ['de_tai_id', 'hoi_dong_id', 'diem', 'nhan_xet'];

    public function findByDeTai(int $deTaiId): array
    {
        return $this->db->table('diem_nghiem_thu')
            ->where('de_tai_id', $deTaiId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): int
    {
        // $data: de_tai_id, hoi_dong_id, diem, [nhan_xet]
        // Rang buoc CHECK (diem >= 0 AND diem <= 10) da co san o schema.sql.
        return $this->insert($data);
    }
}
