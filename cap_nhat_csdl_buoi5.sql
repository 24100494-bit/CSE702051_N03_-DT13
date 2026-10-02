-- ============================================================
-- cap_nhat_csdl_buoi5.sql — nâng CSDL đang chạy (máy chủ trực tuyến) lên bản Buổi 5
-- Chạy MỘT lần trên CSDL đã có dữ liệu. CSDL mới dựng từ schema.sql + seed.sql thì không cần.
--   Thêm cột nguoi_dung.bi_khoa (thư ký khóa/mở tài khoản, F1.6)
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE `nguoi_dung`
  ADD COLUMN `bi_khoa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `so_dien_thoai`;
