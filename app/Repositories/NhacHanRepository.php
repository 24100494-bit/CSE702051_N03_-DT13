<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho nhac han (K9, F6.3): tim cac moc sap het han ma de tai chua nop bao cao.
 * Chi doc; han_nop la DATETIME gio Viet Nam, so voi NOW() cua phien da dat mui gio o BaseRepository.
 * De tai can nop: da duyet hoac dang thuc hien. Moc tinh khi han_nop nam trong ($gio) gio toi.
 */
class NhacHanRepository extends BaseRepository
{
    protected string $table = 'moc_thoi_gian';

    private const COT = 'SELECT dt.id AS de_tai_id, dt.ten_de_tai, m.id AS moc_id, m.ten_moc, m.han_nop
                           FROM de_tai dt
                           JOIN moc_thoi_gian m ON m.lop_hoc_phan_id = dt.lop_hoc_phan_id
                          WHERE dt.trang_thai IN (\'approved\', \'in_progress\')
                            AND m.han_nop > NOW() AND m.han_nop <= NOW() + INTERVAL ? HOUR
                            AND NOT EXISTS (SELECT 1 FROM bao_cao_moc b WHERE b.de_tai_id = dt.id AND b.moc_thoi_gian_id = m.id)';

    /** Cac cap (de tai, moc) can nhac cua mot sinh vien: nguoi de xuat hoac thanh vien nhom */
    public function canNhacCuaSinhVien(int $sinhVienId, int $gio): array
    {
        $sql = self::COT . '
                            AND (dt.sinh_vien_de_xuat_id = ?
                                 OR EXISTS (SELECT 1 FROM thanh_vien_nhom t WHERE t.de_tai_id = dt.id AND t.sinh_vien_id = ?))
                          ORDER BY m.han_nop, dt.id';

        return $this->db->query($sql, [$gio, $sinhVienId, $sinhVienId])->getResultArray();
    }

    /** Moi cap (de tai, moc) can nhac trong toan he thong (tac vu noi bo) */
    public function canNhacToanBo(int $gio): array
    {
        return $this->db->query(self::COT . ' ORDER BY m.han_nop, dt.id', [$gio])->getResultArray();
    }

    /** Nguoi nhan nhac han cua mot de tai: nguoi de xuat va moi thanh vien nhom */
    public function nguoiNhan(int $deTaiId): array
    {
        $sql = 'SELECT sinh_vien_de_xuat_id AS id FROM de_tai WHERE id = ?
                UNION
                SELECT sinh_vien_id FROM thanh_vien_nhom WHERE de_tai_id = ?';

        return array_map('intval', array_column($this->db->query($sql, [$deTaiId, $deTaiId])->getResultArray(), 'id'));
    }

    /** Da co thong bao nhac han cho dung nguoi, dung cap (de tai, moc) chua */
    public function daNhac(int $nguoiDungId, string $tieuDe, string $dauNhan): bool
    {
        return $this->db->table('thong_bao')
            ->where('nguoi_dung_id', $nguoiDungId)
            ->where('tieu_de', $tieuDe)
            ->like('noi_dung', $dauNhan)
            ->countAllResults() > 0;
    }
}
