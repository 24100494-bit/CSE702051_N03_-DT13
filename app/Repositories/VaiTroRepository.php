<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `vai_tro` va bang trung gian `nguoi_dung_vai_tro`.
 */
class VaiTroRepository extends BaseRepository
{
    protected string $table = 'vai_tro';

    protected array $allowedFields = ['ten_vai_tro', 'mo_ta'];

    public function findByTen(string $tenVaiTro): ?array
    {
        $row = $this->db->table('vai_tro')
            ->where('ten_vai_tro', $tenVaiTro)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function ganVaiTroChoNguoiDung(int $nguoiDungId, int $vaiTroId): bool
    {
        return (bool) $this->db->table('nguoi_dung_vai_tro')
            ->ignore(true)
            ->insert(['nguoi_dung_id' => $nguoiDungId, 'vai_tro_id' => $vaiTroId]);
    }

    public function thuHoiVaiTroCuaNguoiDung(int $nguoiDungId, int $vaiTroId): bool
    {
        return (bool) $this->db->table('nguoi_dung_vai_tro')
            ->where('nguoi_dung_id', $nguoiDungId)
            ->where('vai_tro_id', $vaiTroId)
            ->delete();
    }
}
