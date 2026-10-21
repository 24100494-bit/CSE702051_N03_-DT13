-- ============================================================
-- schema.sql — Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu (DT13)
-- Database: quan_ly_do_an (trên InfinityFree là CSDL if0_..., bấm chọn CSDL trước khi nhập)
-- Tạo toàn bộ lược đồ 13 bảng từ CSDL rỗng: khóa chính, khóa ngoại, UNIQUE, CHECK, chỉ mục
-- và dữ liệu khai báo của bảng chuyen_trang_thai_hop_le.
-- Yêu cầu: MariaDB 10.4+ (XAMPP), MariaDB 11.4 (InfinityFree) hoặc MySQL 8.0.16+ (CHECK mới có tác dụng).
--
-- Đã gộp vào tệp này (không cần chạy riêng nữa):
--   cap_nhat_csdl_buoi6.sql  ràng buộc bất biến, trạng thái 'accepted', bảng chuyen_trang_thai_hop_le
--   cap_nhat_csdl_buoi8.sql  bảng ma_dat_lai_mat_khau (khôi phục mật khẩu, K1)
--   indexes_v2.sql, indexes_search.sql, indexes_buoi09.sql  chỉ mục tối ưu truy vấn (Mục 5.4)
-- 5 trigger nằm ở trigger.sql, chạy SAU dữ liệu mẫu (InfinityFree không cấp quyền TRIGGER, lỗi #1142).
--
-- Thứ tự dựng: schema.sql -> seed.sql -> seed_extended.sql -> trigger.sql (chỉ máy cục bộ)
-- Có DROP TABLE IF EXISTS nên chạy lại sẽ xóa dữ liệu cũ.
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
  `bi_khoa`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  UNIQUE KEY `email` (`email`),
  -- Tìm người dùng theo họ tên (tiền tố)
  KEY `idx_nguoidung_hoten` (`ho_ten`(60)),
  CONSTRAINT `chk_nd_bikhoa` CHECK (`bi_khoa` IN (0,1))
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
  -- Lọc lớp học phần theo năm học + học kỳ
  KEY `idx_lophocphan_namhoc_hocky` (`nam_hoc`, `hoc_ky`),
  CONSTRAINT `chk_lophocphan_hocky` CHECK (`hoc_ky` IN ('HK1','HK2','HK3'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- de_tai
-- 'completed' = đủ điều kiện nghiệm thu; 'accepted' = đã nghiệm thu (thư ký khoa chốt)
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
  -- Đề tài chờ duyệt của một lớp, cũ nhất trước (không filesort theo created_at)
  KEY `idx_detai_lop_trangthai_created` (`lop_hoc_phan_id`, `trang_thai`, `created_at`),
  -- Bảng điều khiển GVHD: đề tài mình hướng dẫn theo trạng thái
  KEY `idx_detai_gvhd_trangthai` (`gvhd_id`, `trang_thai`),
  -- Danh sách mới nhất trước (ORDER BY created_at DESC) cho /de-tai, /public/de-tai, /nghiem-thu/ho-so
  KEY `idx_detai_created` (`created_at`),
  -- Tìm đề tài theo tên (tiền tố)
  KEY `idx_detai_ten` (`ten_de_tai`(100)),
  CONSTRAINT `fk_detai_gvhd`        FOREIGN KEY (`gvhd_id`)              REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `fk_detai_lophocphan`  FOREIGN KEY (`lop_hoc_phan_id`)      REFERENCES `lop_hoc_phan` (`id`),
  CONSTRAINT `fk_detai_sv`          FOREIGN KEY (`sinh_vien_de_xuat_id`) REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `chk_detai_trangthai`  CHECK (`trang_thai` IN ('pending','approved','rejected','in_progress','completed','accepted')),
  -- Đề tài đã qua bước chờ duyệt thì phải có GVHD
  CONSTRAINT `chk_detai_gvhd`       CHECK (`trang_thai` IN ('pending','rejected') OR `gvhd_id` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- thanh_vien_nhom
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `thanh_vien_nhom`;
CREATE TABLE `thanh_vien_nhom` (
  `de_tai_id`         BIGINT UNSIGNED NOT NULL,
  `sinh_vien_id`      BIGINT UNSIGNED NOT NULL,
  `vai_tro_nhom`      VARCHAR(20) NOT NULL DEFAULT 'member',
  -- Cột sinh: chỉ có giá trị ở dòng nhóm trưởng, để UNIQUE bảo đảm mỗi đề tài đúng một nhóm trưởng (NULL không tính trùng)
  `leader_de_tai_id`  BIGINT UNSIGNED AS (IF(`vai_tro_nhom` = 'leader', `de_tai_id`, NULL)) VIRTUAL,
  PRIMARY KEY (`de_tai_id`, `sinh_vien_id`),
  UNIQUE KEY `uq_tvn_one_leader` (`leader_de_tai_id`),
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
  -- Mốc không trùng tên trong cùng lớp
  UNIQUE KEY `uq_moc_lop_ten` (`lop_hoc_phan_id`, `ten_moc`),
  KEY `fk_mocthoigian_lop` (`lop_hoc_phan_id`),
  -- Chỉ mục phục vụ truy vấn "các mốc sắp tới của một lớp", sắp xếp theo hạn nộp
  KEY `idx_mocthoigian_lop_hannop` (`lop_hoc_phan_id`, `han_nop`),
  -- Tác vụ nhắc hạn / đánh dấu quá hạn quét mọi lớp theo han_nop
  KEY `idx_mocthoigian_hannop` (`han_nop`),
  CONSTRAINT `fk_mocthoigian_lop` FOREIGN KEY (`lop_hoc_phan_id`) REFERENCES `lop_hoc_phan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_moc_batbuoc` CHECK (`bat_buoc` IN (0,1))
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
  -- Phục vụ truy vấn bản nộp mới nhất theo từng mốc của một đề tài (Buổi 9)
  KEY `idx_baocao_detai_moc_created_id` (`de_tai_id`, `moc_thoi_gian_id`, `created_at`, `id`),
  -- Thống kê tình hình nộp theo mốc (chỉ mục phủ)
  KEY `idx_baocao_moc_trangthai` (`moc_thoi_gian_id`, `trang_thai`),
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
  -- Mỗi thành viên hội đồng chỉ chấm một lần cho mỗi đề tài
  UNIQUE KEY `uq_diem_detai_hoidong` (`de_tai_id`, `hoi_dong_id`),
  KEY `fk_diem_detai` (`de_tai_id`),
  KEY `fk_diem_hoidong` (`hoi_dong_id`),
  -- Điểm trung bình của một đề tài (chỉ mục phủ)
  KEY `idx_diem_detai_diem` (`de_tai_id`, `diem`),
  CONSTRAINT `fk_diem_detai`   FOREIGN KEY (`de_tai_id`)   REFERENCES `de_tai` (`id`),
  CONSTRAINT `fk_diem_hoidong` FOREIGN KEY (`hoi_dong_id`) REFERENCES `nguoi_dung` (`id`),
  CONSTRAINT `chk_diem_khoangdiem` CHECK (`diem` >= 0 AND `diem` <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bảng lõi 4/4: nhat_ky_he_thong (audit log, chỉ ghi thêm – xem trigger.sql)
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
  -- Nhật ký gần đây của một người dùng
  KEY `idx_nhatky_nguoidung_created` (`nguoi_dung_id`, `created_at`),
  -- Lọc nhật ký theo khoảng thời gian
  KEY `idx_nhatky_created` (`created_at`),
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
  CONSTRAINT `fk_thongbao_nguoidung` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_tb_dadoc` CHECK (`da_doc` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ma_dat_lai_mat_khau (khôi phục mật khẩu, K1 – F1.3)
-- Mã do thư ký khoa cấp sau khi kiểm danh tính; chỉ lưu SHA-256, hết hạn sau 15 phút, dùng một lần.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `ma_dat_lai_mat_khau`;
CREATE TABLE `ma_dat_lai_mat_khau` (
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

-- ------------------------------------------------------------
-- chuyen_trang_thai_hop_le: khai báo các bước chuyển trạng thái hợp lệ
-- Khớp DeTaiService::CHUYEN_TRANG_THAI và BaoCaoMocService::CHUYEN_TRANG_THAI; trigger.sql dùng bảng này.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `chuyen_trang_thai_hop_le`;
CREATE TABLE `chuyen_trang_thai_hop_le` (
  `doi_tuong`       VARCHAR(30) NOT NULL,
  `tu_trang_thai`   VARCHAR(50) NOT NULL,
  `den_trang_thai`  VARCHAR(50) NOT NULL,
  PRIMARY KEY (`doi_tuong`, `tu_trang_thai`, `den_trang_thai`),
  CONSTRAINT `chk_cttht_doituong` CHECK (`doi_tuong` IN ('de_tai','bao_cao_moc'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chuyen_trang_thai_hop_le` (`doi_tuong`,`tu_trang_thai`,`den_trang_thai`) VALUES
  ('de_tai','pending','approved'),                 -- GVHD duyệt
  ('de_tai','pending','rejected'),                 -- GVHD từ chối
  ('de_tai','rejected','pending'),                 -- sinh viên sửa lại đề tài bị từ chối (hành động 'sua')
  ('de_tai','approved','in_progress'),             -- nhóm nộp bản báo cáo đầu tiên (hành động 'bat_dau')
  ('de_tai','in_progress','completed'),            -- mọi mốc bắt buộc đã được duyệt (hành động 'du_dieu_kien')
  ('de_tai','completed','accepted'),               -- thư ký khoa chốt nghiệm thu (hành động 'chot_nghiem_thu')
  ('bao_cao_moc','pending','approved'),            -- GVHD duyệt báo cáo
  ('bao_cao_moc','pending','revision_requested');  -- GVHD yêu cầu bổ sung
-- 'nop_lai' của báo cáo không phải UPDATE: hệ thống tạo bản ghi mới (pending), bản cũ giữ revision_requested.

SET FOREIGN_KEY_CHECKS = 1;
