<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Tang truy cap du lieu cho bang `nhat_ky_he_thong` (audit log).
 * Chi cung cap thao tac GHI va DOC - khong ke thua BaseRepository, khong co update/delete
 * de dam bao tinh toan ven cua nhat ky (yeu cau bat buoc cho Buoi 07 va Buoi 08).
 */
class NhatKyHeThongRepository
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function ghiNhan(?int $nguoiDungId, string $hanhDong, ?string $chiTiet = null, ?string $diaChiIp = null): int
    {
        $this->db->table('nhat_ky_he_thong')->insert([
            'nguoi_dung_id' => $nguoiDungId,
            'hanh_dong'     => $hanhDong,
            'chi_tiet'      => $chiTiet,
            'dia_chi_ip'    => $diaChiIp,
        ]);

        return (int) $this->db->insertID();
    }

    public function findGanDayTheoHanhDong(string $hanhDong, int $gioiHan = 50): array
    {
        return $this->db->table('nhat_ky_he_thong')
            ->where('hanh_dong', $hanhDong)
            ->orderBy('created_at', 'DESC')
            ->limit($gioiHan)
            ->get()
            ->getResultArray();
    }
}
