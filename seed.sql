-- ============================================================
-- seed.sql — Dữ liệu mẫu cho Hệ thống Quản lý Đồ án Tốt nghiệp
-- Chạy sau schema.sql. An toàn chạy lại nhiều lần (idempotent theo id cố định).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- vai_tro
INSERT INTO `vai_tro` (`id`,`ten_vai_tro`,`mo_ta`,`created_at`,`updated_at`) VALUES
(1,'THU_KY_KHOA','Thư ký khoa / Quản trị viên hệ thống','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(2,'GVHD','Giảng viên hướng dẫn đồ án','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(3,'SINH_VIEN','Sinh viên thực hiện đồ án','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(4,'HOI_DONG','Thành viên Hội đồng nghiệm thu','2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `mo_ta` = VALUES(`mo_ta`);

-- nguoi_dung
INSERT INTO `nguoi_dung` (`id`,`ten_dang_nhap`,`mat_khau_hash`,`ho_ten`,`email`,`so_dien_thoai`,`created_at`,`updated_at`) VALUES
(1,'thukykhoa','$2a$12$eImiTXuWVxfM37uY4JANjO3.8P2YVn4aZ8EexuGjQJ0R.eH2B.7iG','Thư Ký Khoa CNTT','thuky@huce.edu.vn','0901111111','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(2,'gv_nguyen_van_a','$2a$12$eImiTXuWVxfM37uY4JANjO3.8P2YVn4aZ8EexuGjQJ0R.eH2B.7iG','TS. Nguyễn Văn A','anguyen@huce.edu.vn','0902222222','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(3,'sv_tran_van_b','$2a$12$eImiTXuWVxfM37uY4JANjO3.8P2YVn4aZ8EexuGjQJ0R.eH2B.7iG','Trần Văn B (Nhóm trưởng)','btran@st.huce.edu.vn','0903333333','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(4,'sv_le_thi_c','$2a$12$eImiTXuWVxfM37uY4JANjO3.8P2YVn4aZ8EexuGjQJ0R.eH2B.7iG','Lê Thị C (Thành viên)','cle@st.huce.edu.vn','0904444444','2026-09-23 03:42:43','2026-09-23 03:42:43'),
(5,'hd_pham_van_d','$2a$12$eImiTXuWVxfM37uY4JANjO3.8P2YVn4aZ8EexuGjQJ0R.eH2B.7iG','PGS.TS. Phạm Văn D','dpham@huce.edu.vn','0905555555','2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `ho_ten` = VALUES(`ho_ten`);

-- nguoi_dung_vai_tro
INSERT INTO `nguoi_dung_vai_tro` (`nguoi_dung_id`,`vai_tro_id`) VALUES
(1,1),(2,2),(3,3),(4,3),(5,4)
ON DUPLICATE KEY UPDATE `nguoi_dung_id` = VALUES(`nguoi_dung_id`);

-- lop_hoc_phan
INSERT INTO `lop_hoc_phan` (`id`,`ma_lop`,`ten_lop`,`hoc_ky`,`nam_hoc`,`created_at`,`updated_at`) VALUES
(1,'IT6001_01','Đồ án Tốt nghiệp CNTT K65','HK2','2025-2026','2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `ten_lop` = VALUES(`ten_lop`);

-- de_tai
INSERT INTO `de_tai` (`id`,`ten_de_tai`,`mo_ta_pham_vi`,`trang_thai`,`sinh_vien_de_xuat_id`,`gvhd_id`,`lop_hoc_phan_id`,`created_at`,`updated_at`) VALUES
(1,'Xây dựng Hệ thống Quản lý Đồ án Tốt nghiệp','Ứng dụng Web quản lý quy trình nộp, duyệt và nghiệm thu đồ án','in_progress',3,2,1,'2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `trang_thai` = VALUES(`trang_thai`);

-- thanh_vien_nhom
INSERT INTO `thanh_vien_nhom` (`de_tai_id`,`sinh_vien_id`,`vai_tro_nhom`) VALUES
(1,3,'leader'),(1,4,'member')
ON DUPLICATE KEY UPDATE `vai_tro_nhom` = VALUES(`vai_tro_nhom`);

-- moc_thoi_gian
INSERT INTO `moc_thoi_gian` (`id`,`lop_hoc_phan_id`,`ten_moc`,`mo_ta`,`han_nop`,`bat_buoc`,`created_at`,`updated_at`) VALUES
(1,1,'Mốc 1: Nộp Đề cương chi tiết','Nộp bản phác thảo đề cương định dạng .pdf','2026-03-01 23:59:59',1,'2026-09-23 03:42:43','2026-09-23 03:42:43'),
(2,1,'Mốc 2: Báo cáo Tiến độ Bán phần','Nộp tài liệu thiết kế CSDL và giao diện','2026-04-15 23:59:59',1,'2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `ten_moc` = VALUES(`ten_moc`);

-- bao_cao_moc
INSERT INTO `bao_cao_moc` (`id`,`de_tai_id`,`moc_thoi_gian_id`,`duong_dan_tep`,`ten_tep_goc`,`trang_thai`,`nhan_xet_gv`,`created_at`,`updated_at`) VALUES
(1,1,1,'/uploads/de_tai_1/moc_1/de_cuong_v1.pdf','De_Cuong_Chi_Tiet_Nhom1.pdf','approved','Đề cương chi tiết tốt, duyệt tiếp tục thực hiện.','2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `trang_thai` = VALUES(`trang_thai`);

-- diem_nghiem_thu
INSERT INTO `diem_nghiem_thu` (`id`,`de_tai_id`,`hoi_dong_id`,`diem`,`nhan_xet`,`created_at`,`updated_at`) VALUES
(1,1,5,9.50,'Sản phẩm hoàn thiện cao, giao diện trực quan, đáp ứng đầy đủ yêu cầu.','2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `diem` = VALUES(`diem`);

-- nhat_ky_he_thong
INSERT INTO `nhat_ky_he_thong` (`id`,`nguoi_dung_id`,`hanh_dong`,`chi_tiet`,`dia_chi_ip`,`created_at`) VALUES
(1,3,'SUBMIT_REPORT','Nộp tệp báo cáo thành công cho Mốc 1','192.168.1.15','2026-09-23 03:42:43'),
(2,2,'APPROVE_REPORT','Đã duyệt báo cáo Mốc 1 cho đề tài ID: 1','192.168.1.20','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `hanh_dong` = VALUES(`hanh_dong`);

-- thong_bao
INSERT INTO `thong_bao` (`id`,`nguoi_dung_id`,`tieu_de`,`noi_dung`,`da_doc`,`created_at`,`updated_at`) VALUES
(1,3,'Kết quả duyệt Mốc 1','Báo cáo Mốc 1 của bạn đã được TS. Nguyễn Văn A phê duyệt.',1,'2026-09-23 03:42:43','2026-09-23 03:42:43')
ON DUPLICATE KEY UPDATE `da_doc` = VALUES(`da_doc`);

SET FOREIGN_KEY_CHECKS = 1;
