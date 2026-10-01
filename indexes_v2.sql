-- ============================================================
-- indexes_v2.sql — Bổ sung chỉ mục cho truy vấn của hai luồng nghiệp vụ (V2)
-- CSDL quan_ly_do_an (MariaDB 10.4 trên XAMPP, MariaDB 11.4 trên InfinityFree; MySQL 8.0.16+ cũng chạy được). Chạy SAU schema.sql (+ seed để đo).
--
-- GIẢ ĐỊNH HAI LUỒNG NGHIỆP VỤ (sửa lại nếu nhóm định nghĩa khác):
--   Luồng 1: Đề xuất và duyệt đề tài (UC01 SV đề xuất -> UC02 GVHD phụ trách duyệt / từ chối)
--   Luồng 2: Nộp và duyệt báo cáo mốc (UC03 SV nộp -> UC04 GVHD duyệt / yêu cầu bổ sung
--            -> ghi nhật ký + gửi thông báo). Q9 nghiệm thu (UC06) nằm ngoài hai luồng.
--
-- schema.sql ĐÃ CÓ: (lop_hoc_phan_id, trang_thai) trên de_tai;
--   (lop_hoc_phan_id, han_nop) trên moc_thoi_gian;
--   (de_tai_id, trang_thai, created_at) trên bao_cao_moc;
--   (hanh_dong, created_at) trên nhat_ky_he_thong;
--   (nguoi_dung_id, da_doc, created_at) trên thong_bao.
-- Script này chỉ thêm 6 chỉ mục còn thiếu.
--
-- Chạy 1 lần. Nếu báo "Duplicate key name" nghĩa là chỉ mục đã tồn tại.
-- ============================================================

-- 1) Truy vấn Q1 (Luồng 1): đề tài chờ duyệt của một lớp, cũ nhất trước
--    SELECT id, ten_de_tai FROM de_tai
--    WHERE lop_hoc_phan_id = ? AND trang_thai = 'pending' ORDER BY created_at;
--    Chỉ mục cũ (lop, trang_thai) phải filesort theo created_at.
CREATE INDEX idx_detai_lop_trangthai_created ON de_tai (lop_hoc_phan_id, trang_thai, created_at);

-- 2) Q2 (Luồng 1 + 2): bảng điều khiển GVHD — đề tài mình hướng dẫn theo trạng thái
--    SELECT id, ten_de_tai FROM de_tai WHERE gvhd_id = ? AND trang_thai IN ('approved','in_progress');
--    Cũng là bước đầu của Q4 (báo cáo chờ duyệt của GVHD, xem bên dưới).
CREATE INDEX idx_detai_gvhd_trangthai ON de_tai (gvhd_id, trang_thai);

-- 3) Q5 (Luồng 2): thống kê tình hình nộp theo mốc
--    SELECT trang_thai, COUNT(*) FROM bao_cao_moc WHERE moc_thoi_gian_id = ? GROUP BY trang_thai;
--    Chỉ mục phủ (covering): không cần đọc bảng.
CREATE INDEX idx_baocao_moc_trangthai ON bao_cao_moc (moc_thoi_gian_id, trang_thai);

-- 4) Q6 (Luồng 2): tác vụ nhắc hạn/đánh dấu quá hạn quét MỌI lớp theo han_nop
--    SELECT id, lop_hoc_phan_id FROM moc_thoi_gian WHERE han_nop BETWEEN ? AND ?;
--    Chỉ mục (lop, han_nop) có sẵn không dùng được vì cột dẫn đầu là lop_hoc_phan_id.
CREATE INDEX idx_mocthoigian_hannop ON moc_thoi_gian (han_nop);

-- 5) Q8 (cả hai luồng): nhật ký gần đây của một người dùng
--    SELECT * FROM nhat_ky_he_thong WHERE nguoi_dung_id = ? ORDER BY created_at DESC LIMIT 20;
CREATE INDEX idx_nhatky_nguoidung_created ON nhat_ky_he_thong (nguoi_dung_id, created_at);

-- 6) Q9 (nghiệm thu UC06): điểm trung bình của một đề tài
--    SELECT AVG(diem), COUNT(*) FROM diem_nghiem_thu WHERE de_tai_id = ?;
--    Chỉ mục phủ (de_tai_id, diem).
CREATE INDEX idx_diem_detai_diem ON diem_nghiem_thu (de_tai_id, diem);

ANALYZE TABLE de_tai, bao_cao_moc, moc_thoi_gian, nhat_ky_he_thong, diem_nghiem_thu;

-- ------------------------------------------------------------
-- (TÙY CHỌN) Dọn chỉ mục thừa: các chỉ mục dưới đây là tiền tố của chỉ mục mới/có sẵn.
-- Chỉ chạy khi nhóm đồng ý; khóa ngoại vẫn hoạt động vì đã có chỉ mục khác bắt đầu bằng cùng cột.
-- ------------------------------------------------------------
-- DROP INDEX idx_detai_lop_trangthai ON de_tai;      -- thay bởi idx_detai_lop_trangthai_created
-- DROP INDEX fk_detai_lophocphan     ON de_tai;      -- tiền tố của idx_detai_lop_trangthai_created
-- DROP INDEX fk_detai_gvhd           ON de_tai;      -- tiền tố của idx_detai_gvhd_trangthai
-- DROP INDEX fk_baocao_moc           ON bao_cao_moc; -- tiền tố của idx_baocao_moc_trangthai
-- DROP INDEX fk_nhatky_nguoidung     ON nhat_ky_he_thong; -- tiền tố của idx_nhatky_nguoidung_created
-- DROP INDEX fk_diem_detai           ON diem_nghiem_thu;  -- tiền tố của idx_diem_detai_diem

-- ------------------------------------------------------------
-- KIỂM CHỨNG BẰNG ANALYZE (chụp kết quả TRƯỚC và SAU khi tạo chỉ mục làm bằng chứng)
-- Thứ tự: schema.sql -> seed.sql -> seed_extended.sql -> seed_bulk.sql
--         -> chạy khối ANALYZE (TRƯỚC) -> chạy phần CREATE INDEX -> chạy lại ANALYZE (SAU)
-- Các câu dưới viết cho MariaDB (ANALYZE SELECT). Chạy trên MySQL 8 thì đổi thành EXPLAIN ANALYZE SELECT.
-- Cần nhìn: cột key (tên chỉ mục), rows (ước tính), r_rows (số dòng thực đọc), Extra
-- ("Using filesort" biến mất; "Using index" xuất hiện với chỉ mục phủ).
-- Muốn đo lại "trước": DROP INDEX <tên> ON <bảng>; rồi chạy lại ANALYZE.
-- Lấy thời gian (ms) cho chỉ số "thời gian phản hồi trung vị": ANALYZE FORMAT=JSON SELECT ...
-- rồi đọc r_total_time_ms.
-- ------------------------------------------------------------
ANALYZE SELECT id, ten_de_tai FROM de_tai
 WHERE lop_hoc_phan_id = 1 AND trang_thai = 'pending' ORDER BY created_at;                       -- Q1

ANALYZE SELECT id, ten_de_tai FROM de_tai
 WHERE gvhd_id = 6 AND trang_thai IN ('approved','in_progress');                                 -- Q2

ANALYZE SELECT b.id, b.de_tai_id, b.created_at FROM bao_cao_moc b
 JOIN de_tai d ON d.id = b.de_tai_id
 WHERE d.gvhd_id = 6 AND b.trang_thai = 'pending' ORDER BY b.created_at;                         -- Q4

ANALYZE SELECT trang_thai, COUNT(*) FROM bao_cao_moc
 WHERE moc_thoi_gian_id = 6 GROUP BY trang_thai;                                                 -- Q5

ANALYZE SELECT id, lop_hoc_phan_id FROM moc_thoi_gian
 WHERE han_nop BETWEEN '2026-09-30 00:00:00' AND '2026-10-31 23:59:59';                          -- Q6

ANALYZE SELECT * FROM nhat_ky_he_thong
 WHERE nguoi_dung_id = 25 ORDER BY created_at DESC LIMIT 20;                                     -- Q8

ANALYZE SELECT AVG(diem), COUNT(*) FROM diem_nghiem_thu WHERE de_tai_id = 2;             -- Q9
