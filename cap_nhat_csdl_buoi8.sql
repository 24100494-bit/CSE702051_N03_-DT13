-- ============================================================
-- Cập nhật CSDL Buổi 8 – khôi phục mật khẩu bằng mã đặt lại một lần (K1, F1.3)
-- Chạy trên phpMyAdmin TRƯỚC khi tải mã nguồn mới lên máy chủ. Chạy lại nhiều lần không lỗi.
-- phpMyAdmin có thể cảnh báo "lỗi phân tích" ở dòng CHECK: bộ phân tích của phpMyAdmin không hiểu CHECK, MariaDB vẫn chạy đúng.
--
-- Máy chủ miễn phí không gửi được email, nên mã đặt lại do thư ký khoa cấp sau khi kiểm danh tính
-- (POST /api/v1/nguoi-dung/{id}/ma-dat-lai), người dùng nhập mã ở POST /api/v1/auth/dat-lai-mat-khau.
-- Chỉ lưu SHA-256 của mã; mã hết hạn sau 15 phút và chỉ dùng được một lần.
-- ============================================================

CREATE TABLE IF NOT EXISTS `ma_dat_lai_mat_khau` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id`  BIGINT UNSIGNED NOT NULL,
  `ma_hash`        CHAR(64)        NOT NULL,
  `het_han`        DATETIME        NOT NULL,
  `da_dung`        TINYINT(1)      NOT NULL DEFAULT 0,
  `tao_boi`        BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_madatlai_hash` (`ma_hash`),
  KEY `idx_madatlai_nguoidung` (`nguoi_dung_id`, `da_dung`),
  KEY `fk_madatlai_taoboi` (`tao_boi`),
  CONSTRAINT `fk_madatlai_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_madatlai_taoboi` FOREIGN KEY (`tao_boi`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_madatlai_dadung` CHECK (`da_dung` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kiểm tra sau khi chạy: phải ra 1 dòng tên bảng
-- (không dùng information_schema vì InfinityFree không cấp quyền đọc, báo #1044)
SHOW TABLES LIKE 'ma_dat_lai_mat_khau';
