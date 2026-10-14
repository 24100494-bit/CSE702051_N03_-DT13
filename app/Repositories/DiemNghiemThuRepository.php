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

    /**
     * Thong ke diem cho nhieu de tai trong mot truy van, tranh N+1 o danh sach ho so.
     * Ket qua duoc danh chi muc theo de_tai_id.
     *
     * @param list<int> $deTaiIds
     * @return array<int, array{so_luot_cham:int, diem_trung_binh:float|null}>
     */
    public function thongKeTheoNhieuDeTai(array $deTaiIds): array
    {
        $deTaiIds = array_values(array_unique(array_filter(array_map('intval', $deTaiIds), static fn ($id) => $id > 0)));
        if ($deTaiIds === []) {
            return [];
        }

        $rows = $this->db->table('diem_nghiem_thu')
            ->select('de_tai_id, COUNT(*) AS so_luot_cham, ROUND(AVG(diem), 2) AS diem_trung_binh')
            ->whereIn('de_tai_id', $deTaiIds)
            ->groupBy('de_tai_id')
            ->get()
            ->getResultArray();

        $ketQua = [];
        foreach ($rows as $row) {
            $ketQua[(int) $row['de_tai_id']] = [
                'so_luot_cham' => (int) $row['so_luot_cham'],
                'diem_trung_binh' => $row['diem_trung_binh'] === null ? null : (float) $row['diem_trung_binh'],
            ];
        }

        return $ketQua;
    }

    /** Tra ve cac de tai ma mot thanh vien hoi dong da cham, trong mot truy van. */
    public function cacDeTaiDaCham(array $deTaiIds, int $hoiDongId): array
    {
        $deTaiIds = array_values(array_unique(array_filter(array_map('intval', $deTaiIds), static fn ($id) => $id > 0)));
        if ($deTaiIds === [] || $hoiDongId <= 0) {
            return [];
        }

        $rows = $this->db->table('diem_nghiem_thu')
            ->select('de_tai_id')
            ->whereIn('de_tai_id', $deTaiIds)
            ->where('hoi_dong_id', $hoiDongId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_map('intval', array_column($rows, 'de_tai_id'))));
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
