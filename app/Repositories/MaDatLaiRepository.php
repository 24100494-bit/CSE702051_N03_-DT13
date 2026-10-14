<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `ma_dat_lai_mat_khau` (K1, F1.3).
 * Chi luu SHA-256 cua ma; het han tinh bang NOW() cua phien (BaseRepository da dat mui gio).
 */
class MaDatLaiRepository extends BaseRepository
{
    protected string $table = 'ma_dat_lai_mat_khau';

    /** Huy cac ma chua dung cua nguoi dung roi tao ma moi; tra ve thoi diem het han */
    public function taoMa(int $nguoiDungId, string $maHash, int $phut, int $taoBoi): string
    {
        $this->huyMaCuaNguoiDung($nguoiDungId);
        $this->db->query(
            'INSERT INTO ma_dat_lai_mat_khau (nguoi_dung_id, ma_hash, het_han, tao_boi) VALUES (?, ?, NOW() + INTERVAL ? MINUTE, ?)',
            [$nguoiDungId, $maHash, $phut, $taoBoi]
        );

        return (string) $this->db->query('SELECT het_han FROM ma_dat_lai_mat_khau WHERE id = ?', [(int) $this->db->insertID()])->getRow()->het_han;
    }

    /** Ma con hieu luc (chua dung, chua het han), khoa dong trong giao dich de khong dung hai lan */
    public function timMaConHieuLucForUpdate(string $maHash): ?array
    {
        $row = $this->db->query(
            'SELECT id, nguoi_dung_id FROM ma_dat_lai_mat_khau WHERE ma_hash = ? AND da_dung = 0 AND het_han > NOW() FOR UPDATE',
            [$maHash]
        )->getRowArray();

        return $row ?: null;
    }

    public function huyMaCuaNguoiDung(int $nguoiDungId): void
    {
        $this->db->table('ma_dat_lai_mat_khau')->where('nguoi_dung_id', $nguoiDungId)->where('da_dung', 0)->update(['da_dung' => 1]);
    }
}
