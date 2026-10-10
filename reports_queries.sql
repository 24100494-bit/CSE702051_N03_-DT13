-- =====================================================================
-- reports_queries.sql  |  V2 - Phu trach du lieu  |  Buoi 07
-- Ba truy van tong hop cho ba bao cao. Tuong thich MariaDB 10.4 / 11.4.
-- Dung cac bang: nguoi_dung, lop_hoc_phan, de_tai, thanh_vien_nhom,
-- moc_thoi_gian, bao_cao_moc, diem_nghiem_thu.
--
-- Bao cao 1: De tai theo lop hoc phan va trang thai
-- Bao cao 2: Tien do nop bao cao theo moc thoi gian
-- Bao cao 3: Tai huong dan va ket qua nghiem thu theo GVHD
--
-- Dieu kien thoi gian: @tu_ngay, @den_ngay (tinh tron ngay, ca hai dau).
--   BC1, BC3 loc theo ngay de xuat de tai (de_tai.created_at);
--   BC2 loc theo han nop cua moc (moc_thoi_gian.han_nop).
-- Trong ung dung, hai bien nay la tham so truyen vao truy van (binding).
-- =====================================================================

-- Chay tren phpMyAdmin: may chu InfinityFree de gio UTC-7, phai dat gio Viet Nam
-- thi moi so sanh dung created_at (TIMESTAMP) voi han_nop (DATETIME gio Viet Nam).
SET time_zone = '+07:00';
SET @tu_ngay  = '2026-01-01';
SET @den_ngay = '2026-12-31';

-- ---------------------------------------------------------------------
-- BAO CAO 1: De tai theo lop hoc phan va trang thai
-- Moi lop: tong de tai de xuat trong khoang, so de tai theo tung trang thai,
-- so de tai chua co GVHD, so sinh vien tham gia.
-- Dieu kien thoi gian dat trong ON de lop chua co de tai van hien voi so 0.
-- So sinh vien dem san theo tung de tai (bang con) de khong nhan ban dong.
-- ---------------------------------------------------------------------
SELECT
    lhp.id                                                       AS lop_hoc_phan_id,
    lhp.ma_lop,
    lhp.ten_lop,
    lhp.nam_hoc,
    lhp.hoc_ky,
    COUNT(dt.id)                                                 AS tong_de_tai,
    COUNT(CASE WHEN dt.trang_thai = 'pending'     THEN 1 END)    AS cho_duyet,
    COUNT(CASE WHEN dt.trang_thai = 'approved'    THEN 1 END)    AS da_duyet,
    COUNT(CASE WHEN dt.trang_thai = 'rejected'    THEN 1 END)    AS bi_tu_choi,
    COUNT(CASE WHEN dt.trang_thai = 'in_progress' THEN 1 END)    AS dang_thuc_hien,
    COUNT(CASE WHEN dt.trang_thai = 'completed'   THEN 1 END)    AS du_dieu_kien_nghiem_thu,
    COUNT(CASE WHEN dt.trang_thai = 'accepted'    THEN 1 END)    AS da_nghiem_thu,
    COUNT(CASE WHEN dt.id IS NOT NULL AND dt.gvhd_id IS NULL THEN 1 END) AS chua_co_gvhd,
    COALESCE(SUM(sv.so_sinh_vien), 0)                            AS so_sinh_vien
FROM lop_hoc_phan lhp
LEFT JOIN de_tai dt
       ON dt.lop_hoc_phan_id = lhp.id
      AND dt.created_at >= @tu_ngay
      AND dt.created_at <  @den_ngay + INTERVAL 1 DAY
LEFT JOIN (SELECT de_tai_id, COUNT(*) AS so_sinh_vien
             FROM thanh_vien_nhom
            GROUP BY de_tai_id) sv ON sv.de_tai_id = dt.id
GROUP BY lhp.id, lhp.ma_lop, lhp.ten_lop, lhp.nam_hoc, lhp.hoc_ky
ORDER BY lhp.nam_hoc DESC, lhp.hoc_ky DESC, lhp.ma_lop;

-- ---------------------------------------------------------------------
-- BAO CAO 2: Tien do nop bao cao theo moc thoi gian
-- De tai can nop = de tai cua lop dang o approved / in_progress / completed / accepted.
-- Mot de tai co the nop nhieu lan cho mot moc (nop lai sau khi bi yeu cau bo sung):
--   - dung han / tre xet BAN NOP DAU TIEN (MIN(created_at)) so voi han_nop;
--   - ket qua duyet xet BAN NOP MOI NHAT (MAX(id)).
-- Nho vay moi de tai chi nam o dung mot cot, cac cot cong lai bang so de tai da nop.
-- overdue: chua co buoc chuyen tu dong (F3.9) nhung du lieu mau co san ban ghi o trang thai nay.
-- ---------------------------------------------------------------------
WITH de_tai_can_nop AS (
    SELECT id, lop_hoc_phan_id
      FROM de_tai
     WHERE trang_thai IN ('approved','in_progress','completed','accepted')
),
nop AS (
    SELECT b.moc_thoi_gian_id AS moc_id,
           b.de_tai_id,
           MIN(b.created_at)  AS nop_lan_dau,
           MAX(b.id)          AS ban_moi_nhat_id
      FROM bao_cao_moc b
      JOIN de_tai_can_nop d ON d.id = b.de_tai_id
     GROUP BY b.moc_thoi_gian_id, b.de_tai_id
),
can_nop_theo_lop AS (
    SELECT lop_hoc_phan_id, COUNT(*) AS so_de_tai
      FROM de_tai_can_nop
     GROUP BY lop_hoc_phan_id
)
SELECT
    m.id                                                         AS moc_id,
    lhp.ma_lop,
    m.ten_moc,
    m.han_nop,
    m.bat_buoc,
    COALESCE(c.so_de_tai, 0)                                     AS so_de_tai_can_nop,
    COUNT(n.de_tai_id)                                           AS so_de_tai_da_nop,
    COALESCE(c.so_de_tai, 0) - COUNT(n.de_tai_id)                AS chua_nop,
    COUNT(CASE WHEN n.nop_lan_dau <= m.han_nop THEN 1 END)       AS nop_dung_han,
    COUNT(CASE WHEN n.nop_lan_dau >  m.han_nop THEN 1 END)       AS nop_tre,
    COUNT(CASE WHEN bm.trang_thai = 'pending'            THEN 1 END) AS cho_duyet,
    COUNT(CASE WHEN bm.trang_thai = 'approved'           THEN 1 END) AS da_duyet,
    COUNT(CASE WHEN bm.trang_thai = 'revision_requested' THEN 1 END) AS can_bo_sung,
    COUNT(CASE WHEN bm.trang_thai = 'overdue'            THEN 1 END) AS qua_han,
    ROUND(100 * COUNT(n.de_tai_id) / NULLIF(c.so_de_tai, 0), 1) AS ty_le_nop_pct
FROM moc_thoi_gian m
JOIN lop_hoc_phan lhp           ON lhp.id = m.lop_hoc_phan_id
LEFT JOIN can_nop_theo_lop c    ON c.lop_hoc_phan_id = m.lop_hoc_phan_id
LEFT JOIN nop n                 ON n.moc_id = m.id
LEFT JOIN bao_cao_moc bm        ON bm.id = n.ban_moi_nhat_id
WHERE m.han_nop >= @tu_ngay
  AND m.han_nop <  @den_ngay + INTERVAL 1 DAY
GROUP BY m.id, lhp.ma_lop, m.ten_moc, m.han_nop, m.bat_buoc, c.so_de_tai
ORDER BY lhp.ma_lop, m.han_nop, m.id;

-- ---------------------------------------------------------------------
-- BAO CAO 3: Tai huong dan va ket qua nghiem thu theo GVHD
-- Moi GVHD: so de tai huong dan (de xuat trong khoang) chia theo giai doan,
-- diem nghiem thu chinh thuc. Diem chi tinh cho de tai DA NGHIEM THU (accepted),
-- tuc thu ky khoa da chot; de tai dang cham (completed) chua co diem chinh thuc.
-- Diem moi de tai = trung binh cac luot cham (lam tron 2 chu so, giong luc chot),
-- roi moi lay trung binh theo GVHD de khong lech theo so thanh vien hoi dong.
-- ---------------------------------------------------------------------
SELECT
    gv.id                                                        AS gvhd_id,
    gv.ho_ten                                                    AS giang_vien,
    COUNT(dt.id)                                                 AS so_de_tai_huong_dan,
    COUNT(CASE WHEN dt.trang_thai = 'pending'  THEN 1 END)       AS cho_duyet,
    COUNT(CASE WHEN dt.trang_thai = 'rejected' THEN 1 END)       AS bi_tu_choi,
    COUNT(CASE WHEN dt.trang_thai IN ('approved','in_progress') THEN 1 END) AS dang_thuc_hien,
    COUNT(CASE WHEN dt.trang_thai = 'completed' THEN 1 END)      AS du_dieu_kien_nghiem_thu,
    COUNT(CASE WHEN dt.trang_thai = 'accepted'  THEN 1 END)      AS da_nghiem_thu,
    ROUND(AVG(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END), 2) AS diem_trung_binh,
    MAX(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END) AS diem_cao_nhat,
    MIN(CASE WHEN dt.trang_thai = 'accepted' THEN d.diem_tb END) AS diem_thap_nhat
FROM nguoi_dung gv
JOIN de_tai dt
  ON dt.gvhd_id = gv.id
 AND dt.created_at >= @tu_ngay
 AND dt.created_at <  @den_ngay + INTERVAL 1 DAY
LEFT JOIN (SELECT de_tai_id, ROUND(AVG(diem), 2) AS diem_tb
             FROM diem_nghiem_thu
            GROUP BY de_tai_id) d ON d.de_tai_id = dt.id
GROUP BY gv.id, gv.ho_ten
ORDER BY so_de_tai_huong_dan DESC, diem_trung_binh DESC, gv.ho_ten;

-- ---------------------------------------------------------------------
-- KIEM TRA DOI CHIEU (chay sau ba bao cao, cung @tu_ngay/@den_ngay).
-- Moi truy van tra ve 0 dong la khop; co dong la bao cao sai o dong do.
-- Chup anh ket qua lam minh chung "chay dung".
-- ---------------------------------------------------------------------
-- D1. BC1: tong cot tong_de_tai = so de tai de xuat trong khoang (dem thang tren de_tai)
SELECT 'D1' AS kiem_tra, x.tong_bc1, y.dem_thang
  FROM (SELECT COUNT(dt.id) AS tong_bc1
          FROM lop_hoc_phan lhp
          JOIN de_tai dt ON dt.lop_hoc_phan_id = lhp.id
         WHERE dt.created_at >= @tu_ngay AND dt.created_at < @den_ngay + INTERVAL 1 DAY) x,
       (SELECT COUNT(*) AS dem_thang FROM de_tai
         WHERE created_at >= @tu_ngay AND created_at < @den_ngay + INTERVAL 1 DAY) y
 WHERE x.tong_bc1 <> y.dem_thang;

-- D2. BC1: moi lop, sau cot trang thai cong lai bang tong_de_tai (khong de tai nao bi bo sot)
SELECT 'D2' AS kiem_tra, lhp.ma_lop
  FROM lop_hoc_phan lhp
  JOIN de_tai dt ON dt.lop_hoc_phan_id = lhp.id
 WHERE dt.created_at >= @tu_ngay AND dt.created_at < @den_ngay + INTERVAL 1 DAY
 GROUP BY lhp.id, lhp.ma_lop
HAVING COUNT(*) <> SUM(dt.trang_thai IN ('pending','approved','rejected','in_progress','completed','accepted'));

-- D3. BC2: moi moc, dung han + tre = da nop = cho duyet + da duyet + can bo sung + qua han, va da nop <= can nop
WITH dtc AS (SELECT id, lop_hoc_phan_id FROM de_tai
              WHERE trang_thai IN ('approved','in_progress','completed','accepted')),
     n AS (SELECT b.moc_thoi_gian_id AS moc_id, b.de_tai_id, MIN(b.created_at) AS dau, MAX(b.id) AS cuoi
             FROM bao_cao_moc b JOIN dtc ON dtc.id = b.de_tai_id
            GROUP BY b.moc_thoi_gian_id, b.de_tai_id)
SELECT 'D3' AS kiem_tra, m.id AS moc_id
  FROM moc_thoi_gian m
  LEFT JOIN n ON n.moc_id = m.id
  LEFT JOIN bao_cao_moc bm ON bm.id = n.cuoi
 WHERE m.han_nop >= @tu_ngay AND m.han_nop < @den_ngay + INTERVAL 1 DAY
 GROUP BY m.id, m.lop_hoc_phan_id, m.han_nop
HAVING COUNT(n.de_tai_id) <> COUNT(CASE WHEN n.dau <= m.han_nop THEN 1 END) + COUNT(CASE WHEN n.dau > m.han_nop THEN 1 END)
    OR COUNT(n.de_tai_id) <> COUNT(CASE WHEN bm.trang_thai IN ('pending','approved','revision_requested','overdue') THEN 1 END)
    OR COUNT(n.de_tai_id) > (SELECT COUNT(*) FROM dtc WHERE dtc.lop_hoc_phan_id = m.lop_hoc_phan_id);

-- D4. BC3: tong so_de_tai_huong_dan = so de tai co GVHD de xuat trong khoang
SELECT 'D4' AS kiem_tra, x.tong_bc3, y.dem_thang
  FROM (SELECT COUNT(dt.id) AS tong_bc3 FROM nguoi_dung gv JOIN de_tai dt ON dt.gvhd_id = gv.id
         WHERE dt.created_at >= @tu_ngay AND dt.created_at < @den_ngay + INTERVAL 1 DAY) x,
       (SELECT COUNT(*) AS dem_thang FROM de_tai
         WHERE gvhd_id IS NOT NULL AND created_at >= @tu_ngay AND created_at < @den_ngay + INTERVAL 1 DAY) y
 WHERE x.tong_bc3 <> y.dem_thang;
