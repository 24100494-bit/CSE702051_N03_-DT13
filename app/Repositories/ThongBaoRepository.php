<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `thong_bao`.
 */
class ThongBaoRepository extends BaseRepository
{
    protected string $table = 'thong_bao';

    protected array $allowedFields = ['nguoi_dung_id', 'tieu_de', 'noi_dung', 'da_doc'];

    public function findByNguoiDung(int $nguoiDungId, bool $chiChuaDoc = false): array
    {
        $builder = $this->db->table('thong_bao')->where('nguoi_dung_id', $nguoiDungId);

        if ($chiChuaDoc) {
            $builder->where('da_doc', 0);
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    public function create(array $data): int
    {
        // $data: nguoi_dung_id, tieu_de, noi_dung, [da_doc]
        return $this->insert($data);
    }

    public function danhDauDaDoc(int $id): bool
    {
        return $this->update($id, ['da_doc' => 1]);
    }
}
