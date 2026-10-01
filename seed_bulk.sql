-- ============================================================
-- seed_bulk.sql — Dữ liệu KHỐI LƯỢNG LỚN (TÙY CHỌN) để đo hiệu quả chỉ mục
-- Hệ thống Quản lý Đồ án Tốt nghiệp — CSDL quan_ly_do_an (MariaDB 10.4 trên XAMPP, MariaDB 11.4 trên InfinityFree; MySQL 8.0.16+ cũng chạy được)
--
-- CÁCH CHẠY: chạy SAU schema.sql, seed.sql, seed_extended.sql
-- (dùng lại người dùng 20..219, đề tài 2..81, mốc 1,2,6,7,11,12,16,17).
-- Với ~10.000 dòng trở lên ANALYZE mới cho thấy khác biệt rõ giữa
-- "trước" và "sau" khi thêm chỉ mục (xem indexes_v2.sql).
--
-- KHÔNG dùng bộ này để viết ca kiểm thử nghiệp vụ: dữ liệu sinh theo công
-- thức (mỗi đề tài có rất nhiều phiên bản báo cáo), chỉ phục vụ đo hiệu năng.
-- Id bắt đầu từ 10001 để không đụng seed_extended.sql.
-- CHỈ nạp vào CSDL cục bộ để đo; KHÔNG nạp lên InfinityFree (60.000 dòng, có báo cáo
-- của cả đề tài pending/rejected, làm sai kết quả ca kiểm thử nghiệp vụ).
-- Chạy lại nhiều lần an toàn (id cố định + ON DUPLICATE KEY UPDATE).
-- Muốn đổi số dòng: sửa 20000.
-- ============================================================

SET NAMES utf8mb4;
-- MariaDB không cần đặt giới hạn đệ quy (max_recursive_iterations mặc định rất lớn).
-- Nếu chạy trên MySQL 8 thì thêm dòng: SET SESSION cte_max_recursion_depth = 100000;

-- ------------------------------------------------------------
-- bao_cao_moc: 20.000 dòng, id 10001..30000
-- đề tài 2..81; mốc thuộc đúng lớp của đề tài (lớp = 1 + (id-2) MOD 4)
-- ------------------------------------------------------------
INSERT INTO `bao_cao_moc`
  (`id`,`de_tai_id`,`moc_thoi_gian_id`,`duong_dan_tep`,`ten_tep_goc`,`trang_thai`,`nhan_xet_gv`,`created_at`,`updated_at`)
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 20000
),
b AS (
  SELECT n, 2 + MOD(n, 80) AS de_tai_id, ELT(1 + MOD(n, 10),
         'approved','approved','approved','approved','pending','pending','pending',
         'revision_requested','revision_requested','overdue') AS trang_thai
  FROM seq
)
SELECT
  10000 + n,
  de_tai_id,
  CASE MOD(de_tai_id - 2, 4) WHEN 0 THEN 1 WHEN 1 THEN 6 WHEN 2 THEN 11 ELSE 16 END + MOD(n DIV 80, 2),
  CONCAT('/uploads/bulk/de_tai_', de_tai_id, '/bao_cao_', n, '.pdf'),
  CONCAT('BaoCao_', n, '.pdf'),
  trang_thai,
  IF(trang_thai = 'pending', NULL, CONCAT('Nhận xét tự sinh #', n)),
  DATE_ADD('2026-03-01 00:00:00', INTERVAL n * 600 SECOND),
  DATE_ADD('2026-03-01 00:00:00', INTERVAL n * 600 SECOND)
FROM b
ON DUPLICATE KEY UPDATE
  `de_tai_id` = VALUES(`de_tai_id`), `moc_thoi_gian_id` = VALUES(`moc_thoi_gian_id`),
  `duong_dan_tep` = VALUES(`duong_dan_tep`), `ten_tep_goc` = VALUES(`ten_tep_goc`),
  `trang_thai` = VALUES(`trang_thai`), `nhan_xet_gv` = VALUES(`nhan_xet_gv`),
  `updated_at` = VALUES(`updated_at`);

-- ------------------------------------------------------------
-- nhat_ky_he_thong: 20.000 dòng, id 10001..30000
-- người dùng 20..219 (cứ 50 dòng có 1 dòng nguoi_dung_id NULL)
-- ------------------------------------------------------------
INSERT INTO `nhat_ky_he_thong`
  (`id`,`nguoi_dung_id`,`hanh_dong`,`chi_tiet`,`dia_chi_ip`,`created_at`)
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 20000
)
SELECT
  10000 + n,
  IF(MOD(n, 50) = 0, NULL, 20 + MOD(n * 7, 200)),
  ELT(1 + MOD(n, 6), 'LOGIN','SUBMIT_REPORT','APPROVE_REPORT','REQUEST_REVISION','PROPOSE_TOPIC','VIEW_DASHBOARD'),
  CONCAT('Sự kiện tự sinh #', n),
  CONCAT('10.0.', MOD(n, 250), '.', 1 + MOD(n, 254)),
  DATE_ADD('2026-03-01 00:00:00', INTERVAL n * 600 SECOND)
FROM seq
ON DUPLICATE KEY UPDATE
  `nguoi_dung_id` = VALUES(`nguoi_dung_id`), `hanh_dong` = VALUES(`hanh_dong`),
  `chi_tiet` = VALUES(`chi_tiet`), `dia_chi_ip` = VALUES(`dia_chi_ip`);

-- ------------------------------------------------------------
-- thong_bao: 20.000 dòng, id 10001..30000
-- người dùng 20..219, khoảng 30% chưa đọc
-- ------------------------------------------------------------
INSERT INTO `thong_bao`
  (`id`,`nguoi_dung_id`,`tieu_de`,`noi_dung`,`da_doc`,`created_at`,`updated_at`)
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 20000
)
SELECT
  10000 + n,
  20 + MOD(n * 13, 200),
  CONCAT('Thông báo tự sinh #', n),
  CONCAT('Nội dung thông báo tự sinh số ', n, ' phục vụ đo hiệu năng.'),
  IF(MOD(n, 10) < 3, 0, 1),
  DATE_ADD('2026-03-01 00:00:00', INTERVAL n * 600 SECOND),
  DATE_ADD('2026-03-01 00:00:00', INTERVAL n * 600 SECOND)
FROM seq
ON DUPLICATE KEY UPDATE
  `nguoi_dung_id` = VALUES(`nguoi_dung_id`), `tieu_de` = VALUES(`tieu_de`),
  `noi_dung` = VALUES(`noi_dung`), `da_doc` = VALUES(`da_doc`),
  `updated_at` = VALUES(`updated_at`);

-- Cập nhật thống kê để trình tối ưu chọn kế hoạch đúng trước khi EXPLAIN
ANALYZE TABLE `bao_cao_moc`, `nhat_ky_he_thong`, `thong_bao`, `de_tai`, `moc_thoi_gian`, `diem_nghiem_thu`;
