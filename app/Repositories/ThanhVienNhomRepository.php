<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Tang truy cap du lieu cho bang trung gian `thanh_vien_nhom`
 * (khoa chinh ghep: de_tai_id + sinh_vien_id) - khong ke thua BaseRepository
 * vi khong co cot `id` rieng.
 */
class ThanhVienNhomRepository
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /** Danh sach thanh vien (kem ho ten, email) cua mot de tai */
    public function findByDeTai(int $deTaiId): array
    {
        return $this->db->table('thanh_vien_nhom tvn')
            ->select('tvn.de_tai_id, tvn.sinh_vien_id, tvn.vai_tro_nhom, nd.ho_ten, nd.email')
            ->join('nguoi_dung nd', 'nd.id = tvn.sinh_vien_id')
            ->where('tvn.de_tai_id', $deTaiId)
            ->orderBy('tvn.vai_tro_nhom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function themThanhVien(int $deTaiId, int $sinhVienId, string $vaiTroNhom = 'member'): bool
    {
        return (bool) $this->db->query(
            'INSERT INTO thanh_vien_nhom (de_tai_id, sinh_vien_id, vai_tro_nhom) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE vai_tro_nhom = VALUES(vai_tro_nhom)',
            [$deTaiId, $sinhVienId, $vaiTroNhom]
        );
    }

    public function xoaThanhVien(int $deTaiId, int $sinhVienId): bool
    {
        return (bool) $this->db->table('thanh_vien_nhom')
            ->where('de_tai_id', $deTaiId)
            ->where('sinh_vien_id', $sinhVienId)
            ->delete();
    }

    /** Dung de kiem tra "quyen tren doi tuong": mot sinh vien co thuoc de tai nay khong */
    public function laThanhVien(int $deTaiId, int $sinhVienId): bool
    {
        return $this->db->table('thanh_vien_nhom')
            ->where('de_tai_id', $deTaiId)
            ->where('sinh_vien_id', $sinhVienId)
            ->countAllResults() > 0;
    }
}
