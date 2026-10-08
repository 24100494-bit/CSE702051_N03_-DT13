<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho ba bao cao tong hop (K7), dung dung ba truy van trong reports_queries.sql.
 * Chi doc. Moi tham so di qua binding; $tu, $denTruoc la moc thoi gian 'Y-m-d H:i:s',
 * khoang la [$tu, $denTruoc). $gvhdId khac null thi chi tinh de tai GVHD do huong dan.
 */
class ThongKeRepository extends BaseRepository
{
    protected string $table = 'de_tai';

    /** Bao cao 1: de tai theo lop hoc phan va trang thai (loc theo ngay de xuat) */
    public function deTaiTheoLop(string $tu, string $denTruoc, ?int $gvhdId = null): array
    {
        $sql = "SELECT lhp.id AS lop_hoc_phan_id, lhp.ma_lop, lhp.ten_lop, lhp.nam_hoc, lhp.hoc_ky,
                       COUNT(dt.id) AS tong_de_tai,
                       COUNT(CASE WHEN dt.trang_thai = 'pending'     THEN 1 END) AS cho_duyet,
                       COUNT(CASE WHEN dt.trang_thai = 'approved'    THEN 1 END) AS da_duyet,
                       COUNT(CASE WHEN dt.trang_thai = 'rejected'    THEN 1 END) AS bi_tu_choi,
                       COUNT(CASE WHEN dt.trang_thai = 'in_progress' THEN 1 END) AS dang_thuc_hien,
                       COUNT(CASE WHEN dt.trang_thai = 'completed'   THEN 1 END) AS du_dieu_kien_nghiem_thu,
                       COUNT(CASE WHEN dt.trang_thai = 'accepted'    THEN 1 END) AS da_nghiem_thu,
                       COUNT(CASE WHEN dt.id IS NOT NULL AND dt.gvhd_id IS NULL THEN 1 END) AS chua_co_gvhd,
                       COALESCE(SUM(sv.so_sinh_vien), 0) AS so_sinh_vien
                  FROM lop_hoc_phan lhp
                  LEFT JOIN de_tai dt
                         ON dt.lop_hoc_phan_id = lhp.id
                        AND dt.created_at >= :tu: AND dt.created_at < :den:
                        AND (:gv: IS NULL OR dt.gvhd_id = :gv:)
                  LEFT JOIN (SELECT de_tai_id, COUNT(*) AS so_sinh_vien FROM thanh_vien_nhom GROUP BY de_tai_id) sv
                         ON sv.de_tai_id = dt.id
                 GROUP BY lhp.id, lhp.ma_lop, lhp.ten_lop, lhp.nam_hoc, lhp.hoc_ky
                 ORDER BY lhp.nam_hoc DESC, lhp.hoc_ky DESC, lhp.ma_lop";

        return $this->db->query($sql, ['tu' => $tu, 'den' => $denTruoc, 'gv' => $gvhdId])->getResultArray();
    }

    /**
     * Bao cao 2: tien do nop bao cao theo moc (loc theo han nop).
     * Dung han / tre xet ban nop dau tien; ket qua duyet xet ban nop moi nhat cua tung de tai.
     */
    public function tienDoNop(string $tu, string $denTruoc, ?int $gvhdId = null): array
    {
        $sql = "WITH de_tai_can_nop AS (
                    SELECT id, lop_hoc_phan_id FROM de_tai
                     WHERE trang_thai IN ('approved','in_progress','completed','accepted')
                       AND (:gv: IS NULL OR gvhd_id = :gv:)
                ),
                nop AS (
                    SELECT b.moc_thoi_gian_id AS moc_id, b.de_tai_id,
                           MIN(b.created_at) AS nop_lan_dau, MAX(b.id) AS ban_moi_nhat_id
                      FROM bao_cao_moc b JOIN de_tai_can_nop d ON d.id = b.de_tai_id
                     GROUP BY b.moc_thoi_gian_id, b.de_tai_id
                ),
                can_nop_theo_lop AS (
                    SELECT lop_hoc_phan_id, COUNT(*) AS so_de_tai FROM de_tai_can_nop GROUP BY lop_hoc_phan_id
                )
                SELECT m.id AS moc_id, lhp.ma_lop, m.ten_moc, m.han_nop, m.bat_buoc,
                       COALESCE(c.so_de_tai, 0) AS so_de_tai_can_nop,
                       COUNT(n.de_tai_id) AS so_de_tai_da_nop,
                       COALESCE(c.so_de_tai, 0) - COUNT(n.de_tai_id) AS chua_nop,
                       COUNT(CASE WHEN n.nop_lan_dau <= m.han_nop THEN 1 END) AS nop_dung_han,
                       COUNT(CASE WHEN n.nop_lan_dau >  m.han_nop THEN 1 END) AS nop_tre,
                       COUNT(CASE WHEN bm.trang_thai = 'pending'            THEN 1 END) AS cho_duyet,
                       COUNT(CASE WHEN bm.trang_thai = 'approved'           THEN 1 END) AS da_duyet,
                       COUNT(CASE WHEN bm.trang_thai = 'revision_requested' THEN 1 END) AS can_bo_sung,
                       COUNT(CASE WHEN bm.trang_thai = 'overdue'            THEN 1 END) AS qua_han,
                       ROUND(100 * COUNT(n.de_tai_id) / NULLIF(c.so_de_tai, 0), 1) AS ty_le_nop_pct
                  FROM moc_thoi_gian m
                  JOIN lop_hoc_phan lhp        ON lhp.id = m.lop_hoc_phan_id
                  LEFT JOIN can_nop_theo_lop c ON c.lop_hoc_phan_id = m.lop_hoc_phan_id
                  LEFT JOIN nop n              ON n.moc_id = m.id
                  LEFT JOIN bao_cao_moc bm     ON bm.id = n.ban_moi_nhat_id
                 WHERE m.han_nop >= :tu: AND m.han_nop < :den:
                 GROUP BY m.id, lhp.ma_lop, m.ten_moc, m.han_nop, m.bat_buoc, c.so_de_tai
                 ORDER BY lhp.ma_lop, m.han_nop, m.id";

        return $this->db->query($sql, ['tu' => $tu, 'den' => $denTruoc, 'gv' => $gvhdId])->getResultArray();
    }

    /** Bao cao 3: tai huong dan va ket qua nghiem thu theo GVHD (diem chi tinh de tai da chot) */
    public function theoGvhd(string $tu, string $denTruoc, ?int $gvhdId = null): array
    {
        $sql = "SELECT gv.id AS gvhd_id, gv.ho_ten AS giang_vien,
                       COUNT(dt.id) AS so_de_tai_huong_dan,
                       COUNT(CASE WHEN dt.trang_thai = 'pending'  THEN 1 END) AS cho_duyet,
                       COUNT(CASE WHEN dt.trang_thai = 'rejected' THEN 1 END) AS bi_tu_choi,
                       COUNT(CASE WHEN dt.trang_thai IN ('approved','in_progress') THEN 1 END) AS dang_thuc_hien,
                       COUNT(CASE WHEN dt.trang_thai = 'completed' THEN 1 END) AS du_dieu_kien_nghiem_thu,
                       COUNT(CASE WHEN dt.trang_thai = 'accepted'  THEN 1 END) AS da_nghiem_thu,
                       ROUND(AVG(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END), 2) AS diem_trung_binh,
                       MAX(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END) AS diem_cao_nhat,
                       MIN(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END) AS diem_thap_nhat
                  FROM nguoi_dung gv
                  JOIN de_tai dt
                    ON dt.gvhd_id = gv.id
                   AND dt.created_at >= :tu: AND dt.created_at < :den:
                  LEFT JOIN (SELECT de_tai_id, ROUND(AVG(diem), 2) AS diem_tb FROM diem_nghiem_thu GROUP BY de_tai_id) d
                    ON d.de_tai_id = dt.id
                 WHERE (:gv: IS NULL OR gv.id = :gv:)
                 GROUP BY gv.id, gv.ho_ten
                 ORDER BY so_de_tai_huong_dan DESC, diem_trung_binh DESC, gv.ho_ten";

        return $this->db->query($sql, ['tu' => $tu, 'den' => $denTruoc, 'gv' => $gvhdId])->getResultArray();
    }
}
