-- =====================================================================
-- benchmark_buoi9_sau.sql | V2 - Buoi 9, SAU TOI UU (Anh 28)
-- Tao lai chi muc cua schema.sql, indexes_v2.sql, indexes_search.sql, indexes_buoi09.sql
-- roi EXPLAIN lai dung 5 cau cua benchmark_buoi9_truoc.sql.
-- =====================================================================

-- 1. Tao lai chi muc
CREATE INDEX IF NOT EXISTS idx_detai_lop_trangthai            ON de_tai (lop_hoc_phan_id, trang_thai);
CREATE INDEX IF NOT EXISTS idx_detai_lop_trangthai_created    ON de_tai (lop_hoc_phan_id, trang_thai, created_at);
CREATE INDEX IF NOT EXISTS idx_detai_gvhd_trangthai           ON de_tai (gvhd_id, trang_thai);
CREATE INDEX IF NOT EXISTS idx_detai_created                  ON de_tai (created_at);
CREATE INDEX IF NOT EXISTS idx_detai_ten                      ON de_tai (ten_de_tai(100));
CREATE INDEX IF NOT EXISTS idx_nhatky_hanhdong_created        ON nhat_ky_he_thong (hanh_dong, created_at);
CREATE INDEX IF NOT EXISTS idx_nhatky_nguoidung_created       ON nhat_ky_he_thong (nguoi_dung_id, created_at);
CREATE INDEX IF NOT EXISTS idx_nhatky_created                 ON nhat_ky_he_thong (created_at);
CREATE INDEX IF NOT EXISTS idx_baocao_detai_trangthai_created ON bao_cao_moc (de_tai_id, trang_thai, created_at);
CREATE INDEX IF NOT EXISTS idx_baocao_detai_moc_created_id    ON bao_cao_moc (de_tai_id, moc_thoi_gian_id, created_at, id);
CREATE INDEX IF NOT EXISTS idx_baocao_moc_trangthai           ON bao_cao_moc (moc_thoi_gian_id, trang_thai);
ANALYZE TABLE de_tai, nhat_ky_he_thong, bao_cao_moc;

-- 2. EXPLAIN SAU (chup = Anh 28), so voi Anh 27:
--    D1 ref theo chi muc 3 cot, het "Using filesort"; D2, D4 doc nguoc idx_detai_created 10 - 20 dong;
--    D3 "Using index" (chi doc chi muc, khong doc bang); D5 ref theo idx_nhatky_hanhdong_created 50 dong
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
