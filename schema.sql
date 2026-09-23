-- ============================================================
-- schema.sql — Hệ thống Quản lý Đồ án Tốt nghiệp
-- Database: quan_ly_do_an
-- Tuân thủ Mục 4.2 (Thiết kế cơ sở dữ liệu) của tài liệu CSE702051
-- Cập nhật so với bản dump gốc:
--   1) Thêm ràng buộc CHECK cho mọi cột trạng thái (trang_thai, vai_tro_nhom)
--   2) Thêm CHECK giới hạn khoảng điểm hợp lệ (diem_nghiem_thu.diem)
--   3) Sửa bat_buoc, da_doc thành NOT NULL (khớp ý định thiết kế, khớp từ điển dữ liệu)
--   4) Bổ sung chỉ mục kết hợp cho các truy vấn lọc/sắp xếp thường dùng (Mục 4.2c)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Bảng lõi 1/4: nguoi_dung
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `nguoi_dung`;
CREATE TABLE `nguoi_dung` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_dang_nhap`  VARCHAR(50)  NOT NULL,
  `mat_khau_hash`  VARCHAR(255) NOT NULL,
  `ho_ten`         VARCHAR(100) NOT NULL,
  `email`          VARCHAR(100) NOT NULL,
  `so_dien_thoai`  VARCHAR(15)  DEFAULT NULL,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bảng lõi 2/4: vai_tro
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `vai_tro`;
CREATE TABLE `vai_tro` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_vai_tro`  VARCHAR(50)  NOT NULL,
  `mo_ta`        VARCHAR(255) DEFAULT NULL,
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ten_vai_tro` (`ten_vai_tro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bảng lõi 3/4: nguoi_dung_vai_tro (trung gian N-N)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `nguoi_dung_vai_tro`;
CREATE TABLE `nguoi_dung_vai_tro` (
  `nguoi_dung_id`  BIGINT UNSIGNED NOT NULL,
  `vai_tro_id`     BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`nguoi_dung_id`, `vai_tro_id`),
  KEY `fk_ndvt_vaitro` (`vai_tro_id`),
  CONSTRAINT `fk_ndvt_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ndvt_vaitro`    FOREIGN KEY (`vai_tro_id`)    REFERENCES `vai_tro` (`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- lop_hoc_phan
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `lop_hoc_phan`;
CREATE TABLE `lop_hoc_phan` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_lop`      VARCHAR(20)  NOT NULL,
  `ten_lop`     VARCHAR(100) NOT NULL,
  `hoc_ky`      VARCHAR(20)  NOT NULL,
  `nam_hoc`     VARCHAR(20)  NOT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_lop` (`ma_lop`),
  CONSTRAINT `chk_lophocphan_hocky` CHECK (`hoc_ky` IN ('HK1','HK2','HK3'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- de_tai
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `de_tai`;
CREATE TABLE `de_tai` (
  `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ten_de_tai`             VARCHAR(200) NOT NULL,
  `mo_ta_pham_vi`          TEXT DEFAULT NULL,
  `trang_thai`             VARCHAR(50) NOT NULL DEFAULT 'pending',
  `sinh_vien_de_xuat_id`   BIGINT UNSIGNED NOT NULL,
  `gvhd_id`                BIGINT UNSIGNED DEFAULT NULL,
  `lop_hoc_phan_id`        BIGINT UNSIGNED NOT NULL,
  `created_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_detai_sv` (`sinh_vien_de_xuat_id`),
  KEY `fk_detai_gvhd` (`gvhd_id`),
  KEY `fk_detai_lophocphan` (`lop_hoc_phan_id`),
  -- Chỉ mục kết hợp phục vụ truy vấn "danh sách đề tài theo lớp + trạng thái"
  KEY `idx_detai_lop_trangthai` (`lop_hoc_phan_id`, `trang_thai`),
  CONSTRAINT `fk_detai_gvhd`        FOREIGN KEY (`gvhd_id`)              REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `fk_detai_lophocphan`  FOREIGN KEY (`lop_hoc_phan_id`)      REFERENCES `lop_hoc_phan` (`id`),
  CONSTRAINT `fk_detai_sv`          FOREIGN KEY (`sinh_vien_de_xuat_id`) REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `chk_detai_trangthai`  CHECK (`trang_thai` IN ('pending','approved','rejected','in_progress','completed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- thanh_vien_nhom
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `thanh_vien_nhom`;
CREATE TABLE `thanh_vien_nhom` (
  `de_tai_id`     BIGINT UNSIGNED NOT NULL,
  `sinh_vien_id`  BIGINT UNSIGNED NOT NULL,
  `vai_tro_nhom`  VARCHAR(20) NOT NULL DEFAULT 'member',
  PRIMARY KEY (`de_tai_id`, `sinh_vien_id`),
  KEY `fk_tvn_sv` (`sinh_vien_id`),
  CONSTRAINT `fk_tvn_detai` FOREIGN KEY (`de_tai_id`)    REFERENCES `de_tai` (`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_tvn_sv`    FOREIGN KEY (`sinh_vien_id`)  REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `chk_tvn_vaitronhom` CHECK (`vai_tro_nhom` IN ('leader','member'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- moc_thoi_gian
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `moc_thoi_gian`;
CREATE TABLE `moc_thoi_gian` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lop_hoc_phan_id`  BIGINT UNSIGNED NOT NULL,
  `ten_moc`          VARCHAR(100) NOT NULL,
  `mo_ta`            TEXT DEFAULT NULL,
  `han_nop`          DATETIME NOT NULL,
  `bat_buoc`         TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mocthoigian_lop` (`lop_hoc_phan_id`),
  -- Chỉ mục phục vụ truy vấn "các mốc sắp tới của một lớp", sắp xếp theo hạn nộp
  KEY `idx_mocthoigian_lop_hannop` (`lop_hoc_phan_id`, `han_nop`),
  CONSTRAINT `fk_mocthoigian_lop` FOREIGN KEY (`lop_hoc_phan_id`) REFERENCES `lop_hoc_phan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- bao_cao_moc
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `bao_cao_moc`;
CREATE TABLE `bao_cao_moc` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `de_tai_id`          BIGINT UNSIGNED NOT NULL,
  `moc_thoi_gian_id`   BIGINT UNSIGNED NOT NULL,
  `duong_dan_tep`      VARCHAR(500) NOT NULL,
  `ten_tep_goc`        VARCHAR(255) NOT NULL,
  `trang_thai`         VARCHAR(50) NOT NULL DEFAULT 'pending',
  `nhan_xet_gv`        TEXT DEFAULT NULL,
  `created_at`         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_baocao_detai` (`de_tai_id`),
  KEY `fk_baocao_moc` (`moc_thoi_gian_id`),
  -- Chỉ mục kết hợp phục vụ truy vấn "danh sách báo cáo của một đề tài theo trạng thái, mới nhất trước"
  KEY `idx_baocao_detai_trangthai_created` (`de_tai_id`, `trang_thai`, `created_at`),
  CONSTRAINT `fk_baocao_detai` FOREIGN KEY (`de_tai_id`)        REFERENCES `de_tai` (`id`)        ON DELETE CASCADE,
  CONSTRAINT `fk_baocao_moc`   FOREIGN KEY (`moc_thoi_gian_id`) REFERENCES `moc_thoi_gian` (`id`),
  CONSTRAINT `chk_baocao_trangthai` CHECK (`trang_thai` IN ('pending','approved','revision_requested','overdue'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- diem_nghiem_thu
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `diem_nghiem_thu`;
CREATE TABLE `diem_nghiem_thu` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `de_tai_id`    BIGINT UNSIGNED NOT NULL,
  `hoi_dong_id`  BIGINT UNSIGNED NOT NULL,
  `diem`         DECIMAL(4,2) NOT NULL,
  `nhan_xet`     TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_diem_detai` (`de_tai_id`),
  KEY `fk_diem_hoidong` (`hoi_dong_id`),
  CONSTRAINT `fk_diem_detai`   FOREIGN KEY (`de_tai_id`)   REFERENCES `de_tai` (`id`),
  CONSTRAINT `fk_diem_hoidong` FOREIGN KEY (`hoi_dong_id`) REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `chk_diem_khoangdiem` CHECK (`diem` >= 0 AND `diem` <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bảng lõi 4/4: nhat_ky_he_thong (audit log)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `nhat_ky_he_thong`;
CREATE TABLE `nhat_ky_he_thong` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id`  BIGINT UNSIGNED DEFAULT NULL,
  `hanh_dong`      VARCHAR(100) NOT NULL,
  `chi_tiet`       TEXT DEFAULT NULL,
  `dia_chi_ip`     VARCHAR(45) DEFAULT NULL,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_nhatky_nguoidung` (`nguoi_dung_id`),
  -- Chỉ mục phục vụ truy vấn "nhật ký gần đây theo hành động"
  KEY `idx_nhatky_hanhdong_created` (`hanh_dong`, `created_at`),
  CONSTRAINT `fk_nhatky_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- thong_bao
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `thong_bao`;
CREATE TABLE `thong_bao` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id`  BIGINT UNSIGNED NOT NULL,
  `tieu_de`        VARCHAR(200) NOT NULL,
  `noi_dung`       TEXT NOT NULL,
  `da_doc`         TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_thongbao_nguoidung` (`nguoi_dung_id`),
  -- Chỉ mục phục vụ truy vấn "thông báo chưa đọc của một người dùng, mới nhất trước"
  KEY `idx_thongbao_nguoidung_dadoc_created` (`nguoi_dung_id`, `da_doc`, `created_at`),
  CONSTRAINT `fk_thongbao_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
