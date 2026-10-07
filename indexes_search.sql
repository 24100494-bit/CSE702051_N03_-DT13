-- =====================================================================
-- indexes_search.sql  |  V2 - Phu trach du lieu  |  Buoi 07
-- Chi muc bo sung cho tim kiem / loc (V3) va ba bao cao. Chay duoc tren
-- XAMPP MariaDB 10.4 va InfinityFree MariaDB 11.4; IF NOT EXISTS nen chay lai an toan.
--
-- DA CO SAN, KHONG tao lai:
--   schema.sql:     de_tai(lop_hoc_phan_id, trang_thai), bao_cao_moc(de_tai_id, trang_thai, created_at),
--                   moc_thoi_gian(lop_hoc_phan_id, han_nop), nhat_ky_he_thong(hanh_dong, created_at),
--                   thong_bao(nguoi_dung_id, da_doc, created_at), moi khoa ngoai / UNIQUE
--   indexes_v2.sql: idx_detai_lop_trangthai_created, idx_detai_gvhd_trangthai, idx_baocao_moc_trangthai,
--   (Buoi 05)       idx_mocthoigian_hannop, idx_nhatky_nguoidung_created, idx_diem_detai_diem
-- =====================================================================

-- Tim kiem de tai theo ten bat dau bang tu khoa: LIKE 'abc%' dung duoc chi muc B-tree.
-- LIKE '%abc%' (chua tu khoa o giua) KHONG dung duoc chi muc nay -> bao V3 khi lam tim kiem.
CREATE INDEX IF NOT EXISTS idx_detai_ten ON de_tai (ten_de_tai(100));

-- Bao cao 1, 3 loc de tai theo ngay de xuat
CREATE INDEX IF NOT EXISTS idx_detai_created ON de_tai (created_at);

-- Tim kiem nguoi dung theo ho ten
CREATE INDEX IF NOT EXISTS idx_nguoidung_hoten ON nguoi_dung (ho_ten(60));

-- Loc lop hoc phan theo nam hoc + hoc ky (Bao cao 1)
CREATE INDEX IF NOT EXISTS idx_lophocphan_namhoc_hocky ON lop_hoc_phan (nam_hoc, hoc_ky);

-- Nhat ky he thong: tra cuu theo khoang thoi gian (giao dien tra cuu nhat ky cua V3)
CREATE INDEX IF NOT EXISTS idx_nhatky_created ON nhat_ky_he_thong (created_at);

-- Tim tu khoa o giua ten de tai: bo comment neu nhom dung MATCH ... AGAINST
-- CREATE FULLTEXT INDEX IF NOT EXISTS ft_detai_ten ON de_tai (ten_de_tai);
-- SELECT * FROM de_tai WHERE MATCH(ten_de_tai) AGAINST ('tu khoa' IN BOOLEAN MODE);

-- ---------------------------------------------------------------------
-- MINH CHUNG: chay EXPLAIN truoc va sau khi tao chi muc, chup cot `key` va `rows`
--   EXPLAIN SELECT * FROM de_tai WHERE ten_de_tai LIKE 'Xây dựng%';
--   EXPLAIN SELECT * FROM de_tai WHERE created_at >= '2026-09-01';
--   EXPLAIN SELECT * FROM nhat_ky_he_thong WHERE created_at >= '2026-10-01';
-- Xem chi muc hien co:  SHOW INDEX FROM de_tai;
-- ---------------------------------------------------------------------
