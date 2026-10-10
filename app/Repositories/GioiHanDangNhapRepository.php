<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Dem lan dang nhap sai gan day de chan do mat khau (BM10). Chi doc bang `nhat_ky_he_thong`,
 * dua tren cac dong LOGIN_FAILED do XacThucService ghi. Moi ham tra ve tuoi (giay) cua tung lan sai
 * trong cua so thoi gian, moi nhat truoc. Thoi gian so bang NOW() cua CSDL nen khong phu thuoc mui gio.
 */
class GioiHanDangNhapRepository
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /** Cac lan sai tu mot dia chi IP trong $giay giay gan nhat */
    public function tuoiLanSaiTheoIp(string $diaChiIp, int $giay): array
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS tuoi
                  FROM nhat_ky_he_thong
                 WHERE hanh_dong = 'LOGIN_FAILED' AND dia_chi_ip = ?
                   AND created_at >= NOW() - INTERVAL ? SECOND
                 ORDER BY created_at DESC, id DESC";

        return array_map('intval', array_column($this->db->query($sql, [$diaChiIp, $giay])->getResultArray(), 'tuoi'));
    }

    /** Cac lan sai cua mot tai khoan co that trong $giay giay gan nhat, chi tinh sau lan dang nhap thanh cong cuoi */
    public function tuoiLanSaiTheoTaiKhoan(int $nguoiDungId, int $giay): array
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, f.created_at, NOW()) AS tuoi
                  FROM nhat_ky_he_thong f
                 WHERE f.hanh_dong = 'LOGIN_FAILED' AND f.nguoi_dung_id = ?
                   AND f.created_at >= NOW() - INTERVAL ? SECOND
                   AND NOT EXISTS (SELECT 1 FROM nhat_ky_he_thong s
                                    WHERE s.hanh_dong = 'LOGIN_SUCCESS' AND s.nguoi_dung_id = ? AND s.id > f.id)
                 ORDER BY f.created_at DESC, f.id DESC";

        return array_map('intval', array_column($this->db->query($sql, [$nguoiDungId, $giay, $nguoiDungId])->getResultArray(), 'tuoi'));
    }

    /** Cac lan sai voi mot ten dang nhap khong ton tai (khop dung chi tiet da ghi) trong $giay giay gan nhat */
    public function tuoiLanSaiTheoChiTiet(string $chiTiet, int $giay): array
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS tuoi
                  FROM nhat_ky_he_thong
                 WHERE hanh_dong = 'LOGIN_FAILED' AND nguoi_dung_id IS NULL AND chi_tiet = ?
                   AND created_at >= NOW() - INTERVAL ? SECOND
                 ORDER BY created_at DESC, id DESC";

        return array_map('intval', array_column($this->db->query($sql, [$chiTiet, $giay])->getResultArray(), 'tuoi'));
    }
}
