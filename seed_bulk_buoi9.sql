-- ============================================================
-- seed_bulk_buoi9.sql — Bổ sung 20.000 ĐỀ TÀI để đo hiệu năng Buổi 9
-- Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu — CSDL quan_ly_do_an (MariaDB 10.4 trên XAMPP)
--
-- Bảng chính de_tai mới có khoảng 80 dòng, chưa đạt yêu cầu "tối thiểu 5 000 bản ghi cho
-- bảng chính" của Buổi 9. Tệp này thêm 20.000 đề tài (id 10001..30000) cùng nhóm trưởng và
-- điểm nghiệm thu, để đo GET /de-tai, GET /public/de-tai, GET /nghiem-thu/ho-so.
-- Đo thử: với 5.000 đề tài phần CSDL chỉ vài ms, chìm trong dao động của khung PHP; 20.000 cho
-- chênh lệch trước / sau rõ và lặp lại được. Muốn đổi số dòng: sửa 20000 và 30000 bên dưới.
--
-- CÁCH CHẠY: SAU schema.sql, seed.sql, seed_extended.sql, seed_bulk.sql và các tệp
-- cap_nhat_csdl_buoi5/6/8.sql (cần CHECK trạng thái có 'accepted' của Buổi 6).
-- CHỈ nạp vào CSDL cục bộ để đo; KHÔNG nạp lên InfinityFree.
-- Chạy lại nhiều lần an toàn (id cố định + ON DUPLICATE KEY UPDATE).
--
-- Phân bố trạng thái theo n MOD 10: 0–1 pending, 2 rejected, 3–4 approved, 5–6 in_progress,
-- 7–8 completed, 9 accepted. Lớp 1..4, sinh viên đề xuất 20..219, GVHD 2, 6..13
-- (đề tài pending thì một phần chưa có GVHD). Ngày tạo rải từ 05/01/2026 đến khoảng 30/09/2026.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- de_tai: 20.000 dòng, id 10001..30000
-- ------------------------------------------------------------
INSERT INTO `de_tai`
  (`id`,`ten_de_tai`,`mo_ta_pham_vi`,`trang_thai`,`sinh_vien_de_xuat_id`,`gvhd_id`,`lop_hoc_phan_id`,`created_at`,`updated_at`)
WITH RECURSIVE seq AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1 FROM seq WHERE n < 20000
)
SELECT
  10000 + n,
  CONCAT('Đề tài đo hiệu năng số ', n),
  'Dữ liệu sinh để đo hiệu năng Buổi 9',
  ELT(1 + n MOD 10, 'pending','pending','rejected','approved','approved',
                    'in_progress','in_progress','completed','completed','accepted'),
  20 + n MOD 200,
  IF(n MOD 10 IN (0, 1) AND n MOD 3 = 0, NULL, ELT(1 + n MOD 9, 2, 6, 7, 8, 9, 10, 11, 12, 13)),
  1 + n MOD 4,
  TIMESTAMP('2026-01-05 08:00:00') + INTERVAL ((n * 7919) MOD 380000) MINUTE,
  TIMESTAMP('2026-01-05 08:00:00') + INTERVAL ((n * 7919) MOD 380000) MINUTE
FROM seq
ON DUPLICATE KEY UPDATE `ten_de_tai` = VALUES(`ten_de_tai`);

-- ------------------------------------------------------------
-- thanh_vien_nhom: nhóm trưởng của mỗi đề tài mới
-- ------------------------------------------------------------
INSERT INTO `thanh_vien_nhom` (`de_tai_id`,`sinh_vien_id`,`vai_tro_nhom`)
SELECT `id`, `sinh_vien_de_xuat_id`, 'leader'
FROM `de_tai`
WHERE `id` BETWEEN 10001 AND 30000
ON DUPLICATE KEY UPDATE `vai_tro_nhom` = VALUES(`vai_tro_nhom`);

-- ------------------------------------------------------------
-- diem_nghiem_thu: 2 lượt chấm cho mỗi đề tài accepted (hội đồng 5 và 14), 1 lượt cho
-- một nửa số đề tài completed (hội đồng 5) — để danh sách hồ sơ nghiệm thu có điểm thật
-- ------------------------------------------------------------
INSERT INTO `diem_nghiem_thu` (`de_tai_id`,`hoi_dong_id`,`diem`,`nhan_xet`)
SELECT d.`id`, h.`hoi_dong_id`, 5 + (d.`id` + h.`hoi_dong_id`) MOD 6, 'Điểm sinh để đo hiệu năng'
FROM `de_tai` d
JOIN (SELECT 5 AS hoi_dong_id UNION ALL SELECT 14) h
WHERE d.`id` BETWEEN 10001 AND 30000
  AND (d.`trang_thai` = 'accepted' OR (d.`trang_thai` = 'completed' AND h.`hoi_dong_id` = 5 AND d.`id` MOD 2 = 0))
ON DUPLICATE KEY UPDATE `diem` = VALUES(`diem`);

ANALYZE TABLE `de_tai`, `thanh_vien_nhom`, `diem_nghiem_thu`;

-- Kiểm tra: de_tai phải từ 5.000 dòng trở lên (sau tệp này khoảng 20.080)
SELECT 'de_tai' AS bang, COUNT(*) AS so_dong FROM `de_tai`
UNION ALL SELECT 'thanh_vien_nhom', COUNT(*) FROM `thanh_vien_nhom`
UNION ALL SELECT 'diem_nghiem_thu', COUNT(*) FROM `diem_nghiem_thu`;
