-- ============================================================
-- bo_sung_check.sql - Bo sung 11 rang buoc CHECK sau khi phuc hoi ban ket xuat tu phpMyAdmin InfinityFree.
-- phpMyAdmin 4.9.0.1 tren InfinityFree KHONG dua rang buoc CHECK vao tep xuat (cau CREATE TABLE ket thuc bang ") ;"),
-- du lieu va khoa ngoai thi day du. Tep nay them lai dung 11 CHECK cua luoc do (schema.sql, cap_nhat_csdl_buoi6.sql,
-- cap_nhat_csdl_buoi8.sql). restore_test.bat / .sh tu chay tep nay khi CSDL vua phuc hoi chua co CHECK nao.
-- Chay rieng: mysql -u root quan_ly_do_an_restore_test < bo_sung_check.sql
-- Neu du lieu vi pham mien gia tri thi lenh ALTER bao loi #4025 -> xem muc 4 cua verify_restore.sql.
-- ============================================================

ALTER TABLE `lop_hoc_phan`             ADD CONSTRAINT `chk_lophocphan_hocky` CHECK (`hoc_ky` IN ('HK1','HK2','HK3'));
ALTER TABLE `de_tai`                   ADD CONSTRAINT `chk_detai_trangthai`  CHECK (`trang_thai` IN ('pending','approved','rejected','in_progress','completed','accepted'));
ALTER TABLE `de_tai`                   ADD CONSTRAINT `chk_detai_gvhd`       CHECK (`trang_thai` IN ('pending','rejected') OR `gvhd_id` IS NOT NULL);
ALTER TABLE `thanh_vien_nhom`          ADD CONSTRAINT `chk_tvn_vaitronhom`   CHECK (`vai_tro_nhom` IN ('leader','member'));
ALTER TABLE `bao_cao_moc`              ADD CONSTRAINT `chk_baocao_trangthai` CHECK (`trang_thai` IN ('pending','approved','revision_requested','overdue'));
ALTER TABLE `diem_nghiem_thu`          ADD CONSTRAINT `chk_diem_khoangdiem`  CHECK (`diem` >= 0 AND `diem` <= 10);
ALTER TABLE `moc_thoi_gian`            ADD CONSTRAINT `chk_moc_batbuoc`      CHECK (`bat_buoc` IN (0,1));
ALTER TABLE `nguoi_dung`               ADD CONSTRAINT `chk_nd_bikhoa`        CHECK (`bi_khoa` IN (0,1));
ALTER TABLE `thong_bao`                ADD CONSTRAINT `chk_tb_dadoc`         CHECK (`da_doc` IN (0,1));
ALTER TABLE `chuyen_trang_thai_hop_le` ADD CONSTRAINT `chk_cttht_doituong`   CHECK (`doi_tuong` IN ('de_tai','bao_cao_moc'));
ALTER TABLE `ma_dat_lai_mat_khau`      ADD CONSTRAINT `chk_madatlai_dadung`  CHECK (`da_dung` IN (0,1));
