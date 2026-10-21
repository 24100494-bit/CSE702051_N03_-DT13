-- ============================================================
-- trigger.sql — 5 trigger bảo vệ bất biến nghiệp vụ ở mức CSDL (lớp chặn cuối, sau tầng Service)
-- Chạy SAU schema.sql, seed.sql, seed_extended.sql (trigger chặn sửa/xóa nhật ký và chuyển trạng thái sai).
-- Chỉ chạy trên máy cục bộ (XAMPP): InfinityFree không cấp quyền TRIGGER (lỗi #1142), hệ thống vẫn đúng
-- vì tầng Service đã kiểm tra các điều kiện này.
-- Nếu nhập qua phpMyAdmin bị lỗi DELIMITER: dán từng trigger vào tab SQL và đặt ô "Delimiter" thành $$
-- ============================================================
SET NAMES utf8mb4;

DELIMITER $$

DROP TRIGGER IF EXISTS `trg_nhatky_khong_sua`$$
CREATE TRIGGER `trg_nhatky_khong_sua` BEFORE UPDATE ON `nhat_ky_he_thong`
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nhat ky chi duoc ghi them';
END$$

DROP TRIGGER IF EXISTS `trg_nhatky_khong_xoa`$$
CREATE TRIGGER `trg_nhatky_khong_xoa` BEFORE DELETE ON `nhat_ky_he_thong`
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nhat ky chi duoc ghi them';
END$$

DROP TRIGGER IF EXISTS `trg_detai_chuyen_trangthai`$$
CREATE TRIGGER `trg_detai_chuyen_trangthai` BEFORE UPDATE ON `de_tai`
FOR EACH ROW
BEGIN
  IF NEW.trang_thai <> OLD.trang_thai AND NOT EXISTS (
       SELECT 1 FROM chuyen_trang_thai_hop_le
        WHERE doi_tuong = 'de_tai'
          AND tu_trang_thai = OLD.trang_thai
          AND den_trang_thai = NEW.trang_thai) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chuyen trang thai de tai khong hop le';
  END IF;
END$$

DROP TRIGGER IF EXISTS `trg_baocao_chuyen_trangthai`$$
CREATE TRIGGER `trg_baocao_chuyen_trangthai` BEFORE UPDATE ON `bao_cao_moc`
FOR EACH ROW
BEGIN
  IF NEW.trang_thai <> OLD.trang_thai AND NOT EXISTS (
       SELECT 1 FROM chuyen_trang_thai_hop_le
        WHERE doi_tuong = 'bao_cao_moc'
          AND tu_trang_thai = OLD.trang_thai
          AND den_trang_thai = NEW.trang_thai) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chuyen trang thai bao cao khong hop le';
  END IF;
END$$

-- Gioi han so thanh vien nhom (5 la gia dinh, thay bang so trong de bai).
-- Tang Service (NhomSinhVienService) da kiem tra truoc va tra 422 NHOM_DA_DU; trigger la lop chan cuoi
-- khi co ai ghi thang CSDL. Trigger KHONG chong duoc 2 yeu cau dong thoi: Service khoa hang de_tai (FOR UPDATE) truoc khi dem.
DROP TRIGGER IF EXISTS `trg_tvn_toi_da`$$
CREATE TRIGGER `trg_tvn_toi_da` BEFORE INSERT ON `thanh_vien_nhom`
FOR EACH ROW
BEGIN
  IF (SELECT COUNT(*) FROM thanh_vien_nhom WHERE de_tai_id = NEW.de_tai_id) >= 5 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nhom da du so thanh vien toi da';
  END IF;
END$$

DELIMITER ;
