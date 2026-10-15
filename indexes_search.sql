-- =====================================================================
-- indexes_search.sql | V2 - Chi muc phuc vu TIM KIEM / LOC (V3) - bo sung cho indexes_v2.sql
-- CSDL quan_ly_do_an | MariaDB 10.4 (XAMPP) va 11.4 (InfinityFree). IF NOT EXISTS nen chay lai an toan.
-- Chay trong CSDL dang chon (phpMyAdmin: bam vao ten CSDL truoc). Khong dung lenh USE vi
-- tren InfinityFree ten CSDL khac (if0_...) va khong duoc truy cap CSDL khac (#1044).
--
-- KHONG tao lai cac chi muc da co:
--   schema.sql   : (lop_hoc_phan_id, trang_thai) va (lop_hoc_phan_id, han_nop), (de_tai_id, trang_thai, created_at),
--                  (de_tai_id, moc_thoi_gian_id, created_at, id), (hanh_dong, created_at),
--                  (nguoi_dung_id, da_doc, created_at) + moi khoa ngoai / UNIQUE
--   indexes_v2.sql: idx_detai_lop_trangthai_created, idx_detai_gvhd_trangthai, idx_baocao_moc_trangthai,
--                  idx_mocthoigian_hannop, idx_nhatky_nguoidung_created, idx_diem_detai_diem
--
-- Luu y: CI4 like() mac dinh dung '%tu_khoa%' (khop o giua) nen hai chi muc ten / ho ten duoi day
-- CHI giup khi truy van doi sang tien to 'tu_khoa%'. Xem ghi chu cuoi file.
-- =====================================================================

-- Danh sach de tai moi nhat truoc: GET /de-tai (thu ky), GET /public/de-tai, GET /nghiem-thu/ho-so
-- deu ORDER BY created_at DESC, id DESC LIMIT n -> doc nguoc chi muc, khong filesort ca bang
CREATE INDEX IF NOT EXISTS idx_detai_created           ON de_tai (created_at);
-- Tim de tai theo ten (tien to)
CREATE INDEX IF NOT EXISTS idx_detai_ten               ON de_tai (ten_de_tai(100));
-- Tim nguoi dung theo ho ten (tien to)
CREATE INDEX IF NOT EXISTS idx_nguoidung_hoten         ON nguoi_dung (ho_ten(60));
-- Loc lop hoc phan theo nam hoc + hoc ky
CREATE INDEX IF NOT EXISTS idx_lophocphan_namhoc_hocky ON lop_hoc_phan (nam_hoc, hoc_ky);
-- Loc nhat ky he thong theo khoang thoi gian (khong kem hanh_dong / nguoi_dung)
CREATE INDEX IF NOT EXISTS idx_nhatky_created          ON nhat_ky_he_thong (created_at);

-- Tim tu khoa nam GIUA ten (LIKE '%abc%' khong dung duoc B-tree). Neu nhom muon, dung FULLTEXT:
-- CREATE FULLTEXT INDEX IF NOT EXISTS ft_detai_ten ON de_tai (ten_de_tai);
-- SELECT * FROM de_tai WHERE MATCH(ten_de_tai) AGAINST ('tu khoa' IN BOOLEAN MODE);

-- Minh chung: chay EXPLAIN truoc va sau, chup cot key va rows
--   EXPLAIN SELECT * FROM de_tai WHERE ten_de_tai LIKE 'He thong%';
--   SHOW INDEX FROM de_tai;
