-- ============================================================
-- doi_soat.sql (V2) - moi truy van phai tra ve 0 dong.
-- Dua ket qua (anh chup) vao bao cao lam bang chung "du lieu nhat quan".
-- ============================================================

-- ---------- A. TRUOC KHI ALTER (chay truoc cap_nhat_csdl_buoi6.sql) ----------
-- A1. De tai da duyet nhung thieu GVHD  (vi pham chk_detai_gvhd)
SELECT id, trang_thai FROM de_tai
 WHERE trang_thai NOT IN ('pending','rejected') AND gvhd_id IS NULL;

-- A2. De tai co nhieu hon 1 leader  (vi pham uq_tvn_one_leader)
SELECT de_tai_id, COUNT(*) AS so_leader FROM thanh_vien_nhom
 WHERE vai_tro_nhom = 'leader' GROUP BY de_tai_id HAVING COUNT(*) > 1;

-- A3. Cham diem trung (vi pham uq_diem_detai_hoidong)
SELECT de_tai_id, hoi_dong_id, COUNT(*) FROM diem_nghiem_thu
 GROUP BY de_tai_id, hoi_dong_id HAVING COUNT(*) > 1;

-- A4. Moc trung ten trong cung lop (vi pham uq_moc_lop_ten)
SELECT lop_hoc_phan_id, ten_moc, COUNT(*) FROM moc_thoi_gian
 GROUP BY lop_hoc_phan_id, ten_moc HAVING COUNT(*) > 1;

-- A5. Nhom da vuot gioi han thanh vien
SELECT de_tai_id, COUNT(*) AS so_tv FROM thanh_vien_nhom
 GROUP BY de_tai_id HAVING COUNT(*) > 5;

-- ---------- B. DOI SOAT SAU KHI CHAY ----------
-- Chi xet de tai tao qua API (co dong nhat ky PROPOSE_TOPIC). De tai chen thang bang seed.sql
-- khong co nhat ky nen duoc bo qua, nho vay B1, B2 ra dung 0 dong khi du lieu nhat quan.
-- Dung REGEXP de 'de tai ID: 1' khong khop nham 'de tai ID: 12'.

-- B1. De tai dang 'approved' nhung khong co dong nhat ky APPROVE_TOPIC tuong ung.
SELECT d.id FROM de_tai d
 WHERE d.trang_thai = 'approved'
   AND EXISTS (SELECT 1 FROM nhat_ky_he_thong p
                WHERE p.hanh_dong = 'PROPOSE_TOPIC'
                  AND p.chi_tiet REGEXP CONCAT('de tai ID: ', d.id, '([^0-9]|$)'))
   AND NOT EXISTS (SELECT 1 FROM nhat_ky_he_thong n
                    WHERE n.hanh_dong = 'APPROVE_TOPIC'
                      AND n.chi_tiet REGEXP CONCAT('de tai ID: ', d.id, '([^0-9]|$)'));

-- B2. Tuong tu cho de tai 'rejected' (can nhat ky REJECT_TOPIC)
SELECT d.id FROM de_tai d
 WHERE d.trang_thai = 'rejected'
   AND EXISTS (SELECT 1 FROM nhat_ky_he_thong p
                WHERE p.hanh_dong = 'PROPOSE_TOPIC'
                  AND p.chi_tiet REGEXP CONCAT('de tai ID: ', d.id, '([^0-9]|$)'))
   AND NOT EXISTS (SELECT 1 FROM nhat_ky_he_thong n
                    WHERE n.hanh_dong = 'REJECT_TOPIC'
                      AND n.chi_tiet REGEXP CONCAT('de tai ID: ', d.id, '([^0-9]|$)'));

-- B3. Diem ngoai khoang 0..10 (du phong, CHECK da chan)
SELECT id, diem FROM diem_nghiem_thu WHERE diem < 0 OR diem > 10;

-- B4. Luong 3: de tai da nghiem thu (accepted) ma chua co luot cham nao
SELECT d.id FROM de_tai d
 WHERE d.trang_thai = 'accepted'
   AND NOT EXISTS (SELECT 1 FROM diem_nghiem_thu x WHERE x.de_tai_id = d.id);

-- B5. Luong 3: diem cham cho de tai chua du dieu kien nghiem thu (chi xet de tai tao qua API nhu B1;
--     seed.sql co san diem cho de tai 1 dang in_progress)
SELECT x.id, x.de_tai_id, d.trang_thai FROM diem_nghiem_thu x
  JOIN de_tai d ON d.id = x.de_tai_id
 WHERE d.trang_thai NOT IN ('completed','accepted')
   AND EXISTS (SELECT 1 FROM nhat_ky_he_thong p
                WHERE p.hanh_dong = 'PROPOSE_TOPIC'
                  AND p.chi_tiet REGEXP CONCAT('de tai ID: ', d.id, '([^0-9]|$)'));

-- ---------- C. KIEM CHUNG RANG BUOC HOAT DONG (cho 'ca am' trong bo kiem thu) ----------
-- Moi lenh duoi day PHAI bao loi. Chup man hinh loi lam bang chung.
-- Chay trong transaction roi ROLLBACK de khong doi du lieu that.
-- C1. Chuyen trang thai sai (approved -> pending):
--       UPDATE de_tai SET trang_thai='pending' WHERE trang_thai='approved' LIMIT 1;
-- C2. Sua nhat ky:   UPDATE nhat_ky_he_thong SET chi_tiet='x' LIMIT 1;
-- C3. Xoa nhat ky:   DELETE FROM nhat_ky_he_thong LIMIT 1;
-- C4. Diem ngoai khoang:  UPDATE diem_nghiem_thu SET diem = 11 LIMIT 1;
-- C5. Chuyen hop le van chay duoc (rejected -> pending): phai KHONG loi
--       UPDATE de_tai SET trang_thai='pending' WHERE trang_thai='rejected' LIMIT 1;
