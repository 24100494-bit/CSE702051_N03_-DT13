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

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    public function create(array $data): int
    {
        // $data: de_tai_id, moc_thoi_gian_id, duong_dan_tep, ten_tep_goc, [trang_thai]
        $data['trang_thai'] ??= 'pending';

        return $this->insert($data);
    }

    public function duyet(int $id, string $nhanXet): bool
    {
        return $this->update($id, ['trang_thai' => 'approved', 'nhan_xet_gv' => $nhanXet]);
    }

    public function yeuCauSuaLai(int $id, string $nhanXet): bool
    {
        return $this->update($id, ['trang_thai' => 'revision_requested', 'nhan_xet_gv' => $nhanXet]);
    }
}
