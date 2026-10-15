-- =====================================================================
-- benchmark_buoi9_truoc.sql | V2 - Buoi 9, TRUOC TOI UU (Anh 27)
-- CSDL quan_ly_do_an tren XAMPP (chay cuc bo, KHONG chay tren InfinityFree).
-- "Truoc toi uu" = chi con khoa chinh, UNIQUE va chi muc khoa ngoai (fk_*).
-- =====================================================================

-- 1. So dong: de_tai >= 5.000 (sau seed_bulk_buoi9.sql khoang 20.000)
SELECT 'de_tai' AS bang, COUNT(*) AS so_dong FROM de_tai
UNION ALL SELECT 'bao_cao_moc', COUNT(*) FROM bao_cao_moc
UNION ALL SELECT 'nhat_ky_he_thong', COUNT(*) FROM nhat_ky_he_thong;

-- 2. Go chi muc toi uu
DROP INDEX IF EXISTS idx_detai_lop_trangthai            ON de_tai;
DROP INDEX IF EXISTS idx_detai_lop_trangthai_created    ON de_tai;
DROP INDEX IF EXISTS idx_detai_gvhd_trangthai           ON de_tai;
DROP INDEX IF EXISTS idx_detai_created                  ON de_tai;
DROP INDEX IF EXISTS idx_detai_ten                      ON de_tai;
DROP INDEX IF EXISTS idx_nhatky_hanhdong_created        ON nhat_ky_he_thong;
DROP INDEX IF EXISTS idx_nhatky_nguoidung_created       ON nhat_ky_he_thong;
DROP INDEX IF EXISTS idx_nhatky_created                 ON nhat_ky_he_thong;
DROP INDEX IF EXISTS idx_baocao_detai_trangthai_created ON bao_cao_moc;
DROP INDEX IF EXISTS idx_baocao_detai_moc_created_id    ON bao_cao_moc;
DROP INDEX IF EXISTS idx_baocao_moc_trangthai           ON bao_cao_moc;
ANALYZE TABLE de_tai, nhat_ky_he_thong, bao_cao_moc;

-- 3. EXPLAIN TRUOC (chup = Anh 27): type ALL / key NULL la quet toan bang
-- D1. GET /de-tai?lop_hoc_phan_id=1&trang_thai=in_progress
EXPLAIN SELECT * FROM de_tai WHERE lop_hoc_phan_id = 1 AND trang_thai = 'in_progress' ORDER BY created_at DESC, id DESC LIMIT 20;
-- D2. GET /public/de-tai
EXPLAIN SELECT dt.id, dt.ten_de_tai, dt.trang_thai, lhp.ma_lop FROM de_tai dt LEFT JOIN lop_hoc_phan lhp ON lhp.id = dt.lop_hoc_phan_id
WHERE dt.trang_thai IN ('approved','in_progress','completed','accepted') ORDER BY dt.created_at DESC, dt.id DESC LIMIT 10;
-- D3. GET /thong-ke/tien-do-nop (phan gom ban nop theo moc, de tai)
EXPLAIN SELECT b.moc_thoi_gian_id, b.de_tai_id, MIN(b.created_at), MAX(b.id) FROM bao_cao_moc b
JOIN de_tai d ON d.id = b.de_tai_id AND d.trang_thai IN ('approved','in_progress','completed','accepted')
GROUP BY b.moc_thoi_gian_id, b.de_tai_id;
-- D4. GET /nghiem-thu/ho-so
EXPLAIN SELECT * FROM de_tai WHERE trang_thai IN ('completed','accepted') ORDER BY created_at DESC, id DESC LIMIT 20;
-- D5. GET /nhat-ky-he-thong?hanh_dong=APPROVE_TOPIC
EXPLAIN SELECT nk.*, nd.ho_ten FROM nhat_ky_he_thong nk LEFT JOIN nguoi_dung nd ON nd.id = nk.nguoi_dung_id
WHERE nk.hanh_dong = 'APPROVE_TOPIC' ORDER BY nk.created_at DESC, nk.id DESC LIMIT 20;
