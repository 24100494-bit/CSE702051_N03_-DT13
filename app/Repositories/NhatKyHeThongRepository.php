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

    /** Cac dong nhat ky lam doi trang thai de tai; khop dung ma (ID 1 khong khop 14, 10...) */
    public function findLichSuDeTai(int $deTaiId): array
    {
        return $this->findTheoDoiTuong('de tai ID: ' . $deTaiId, ['PROPOSE_TOPIC', 'APPROVE_TOPIC', 'REJECT_TOPIC', 'RESUBMIT_TOPIC', 'START_TOPIC', 'TOPIC_READY', 'ACCEPT_TOPIC']);
    }

    /** Cac dong nhat ky lam doi trang thai mot ban bao cao; khop dung ma */
    public function findLichSuBaoCao(int $baoCaoId): array
    {
        return $this->findTheoDoiTuong('bao cao ID: ' . $baoCaoId, ['SUBMIT_REPORT', 'RESUBMIT_REPORT', 'APPROVE_REPORT', 'REQUEST_REVISION']);
    }

    /**
     * Loc theo hanh dong va chuoi "<doi tuong> ID: <ma>" theo sau khong phai chu so.
     * Ma la so nguyen do Service truyen vao nen an toan khi dat vao mau REGEXP.
     */
    private function findTheoDoiTuong(string $mau, array $hanhDong): array
    {
        return $this->db->table('nhat_ky_he_thong')
            ->whereIn('hanh_dong', $hanhDong)
            ->where('chi_tiet REGEXP ' . $this->db->escape($mau . '([^0-9]|$)'), null, false)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
