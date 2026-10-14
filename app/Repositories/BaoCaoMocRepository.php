<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `bao_cao_moc`.
 */
class BaoCaoMocRepository extends BaseRepository
{
    protected string $table = 'bao_cao_moc';

    protected array $allowedFields = ['de_tai_id', 'moc_thoi_gian_id', 'duong_dan_tep', 'ten_tep_goc', 'trang_thai', 'nhan_xet_gv'];

    public function findByDeTai(int $deTaiId, ?string $trangThai = null): array
    {
        $builder = $this->db->table('bao_cao_moc')->where('de_tai_id', $deTaiId);

        if ($trangThai !== null) {
            $builder->where('trang_thai', $trangThai);
        }

        return $builder->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /** Ban nop moi nhat cua mot de tai cho mot moc (moi lan nop lai la mot ban ghi moi) */
    public function findMoiNhat(int $deTaiId, int $mocThoiGianId): ?array
    {
        $row = $this->db->table('bao_cao_moc')
            ->where('de_tai_id', $deTaiId)
            ->where('moc_thoi_gian_id', $mocThoiGianId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Lay ban nop moi nhat cua nhieu moc trong cung mot de tai bang mot truy van.
     * Thu tu created_at/id giam dan dam bao dong dau tien moi moc la ban nop moi nhat.
     *
     * @param list<int> $mocThoiGianIds
     * @return array<int, array> Chi muc theo moc_thoi_gian_id
     */
    public function findMoiNhatTheoNhieuMoc(int $deTaiId, array $mocThoiGianIds): array
    {
        $mocThoiGianIds = array_values(array_unique(array_filter(array_map('intval', $mocThoiGianIds), static fn ($id) => $id > 0)));
        if ($deTaiId <= 0 || $mocThoiGianIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($mocThoiGianIds), '?'));
        $sql = "SELECT b.*
                  FROM bao_cao_moc b
                 WHERE b.de_tai_id = ?
                   AND b.moc_thoi_gian_id IN ($placeholders)
                   AND NOT EXISTS (
                       SELECT 1
                         FROM bao_cao_moc b2
                        WHERE b2.de_tai_id = b.de_tai_id
                          AND b2.moc_thoi_gian_id = b.moc_thoi_gian_id
                          AND (b2.created_at > b.created_at
                               OR (b2.created_at = b.created_at AND b2.id > b.id))
                   )
                 ORDER BY b.created_at DESC, b.id DESC";
        $rows = $this->db->query($sql, array_merge([$deTaiId], $mocThoiGianIds))->getResultArray();

        $ketQua = [];
        foreach ($rows as $row) {
            $mocId = (int) $row['moc_thoi_gian_id'];
            if (!isset($ketQua[$mocId])) {
                $ketQua[$mocId] = $row;
            }
        }

        return $ketQua;
    }

    /** Doc va khoa dong bao cao trong giao dich */
    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->db->query('SELECT * FROM bao_cao_moc WHERE id = ? FOR UPDATE', [$id])->getRowArray();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        // $data: de_tai_id, moc_thoi_gian_id, duong_dan_tep, ten_tep_goc, [trang_thai]
        $data['trang_thai'] ??= 'pending';

        return $this->insert($data);
    }

    public function duyet(int $id, ?string $nhanXet): bool
    {
        return $this->update($id, ['trang_thai' => 'approved', 'nhan_xet_gv' => $nhanXet]);
    }

    public function yeuCauSuaLai(int $id, string $nhanXet): bool
    {
        return $this->update($id, ['trang_thai' => 'revision_requested', 'nhan_xet_gv' => $nhanXet]);
    }

    /** So moc bat buoc cua lop ma de tai da co ban nop duoc duyet (moi moc tinh mot lan) */
    public function demMocBatBuocDaDuyet(int $deTaiId, int $lopHocPhanId): int
    {
        $row = $this->db->table('bao_cao_moc b')
            ->select('COUNT(DISTINCT b.moc_thoi_gian_id) AS so_moc')
            ->join('moc_thoi_gian m', 'm.id = b.moc_thoi_gian_id')
            ->where('b.de_tai_id', $deTaiId)
            ->where('b.trang_thai', 'approved')
            ->where('m.bat_buoc', 1)
            ->where('m.lop_hoc_phan_id', $lopHocPhanId)
            ->get()
            ->getRowArray();

        return (int) ($row['so_moc'] ?? 0);
    }
}
