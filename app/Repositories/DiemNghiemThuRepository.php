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

    /** Diem cua tung thanh vien hoi dong kem ho ten nguoi cham */
    public function findByDeTaiKemNguoiCham(int $deTaiId): array
    {
        return $this->db->table('diem_nghiem_thu d')
            ->select('d.id, d.de_tai_id, d.hoi_dong_id, nd.ho_ten AS nguoi_cham, d.diem, d.nhan_xet, d.created_at')
            ->join('nguoi_dung nd', 'nd.id = d.hoi_dong_id')
            ->where('d.de_tai_id', $deTaiId)
            ->orderBy('d.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function daCham(int $deTaiId, int $hoiDongId): bool
    {
        return $this->db->table('diem_nghiem_thu')
            ->where('de_tai_id', $deTaiId)
            ->where('hoi_dong_id', $hoiDongId)
            ->countAllResults() > 0;
    }

    /** So luot cham va diem trung binh (lam tron 2 chu so) cua mot de tai */
    public function thongKe(int $deTaiId): array
    {
        $row = $this->db->table('diem_nghiem_thu')
            ->select('COUNT(*) AS so_luot, ROUND(AVG(diem), 2) AS diem_tb')
            ->where('de_tai_id', $deTaiId)
            ->get()
            ->getRowArray();

        return [
            'so_luot_cham' => (int) ($row['so_luot'] ?? 0),
            'diem_trung_binh' => $row['diem_tb'] === null ? null : (float) $row['diem_tb'],
        ];
    }
}
