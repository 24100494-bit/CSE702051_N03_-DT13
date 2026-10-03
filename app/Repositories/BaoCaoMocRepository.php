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
