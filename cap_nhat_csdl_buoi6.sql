-- ============================================================
-- cap_nhat_csdl_buoi6.sql  (V2 - phu trach du lieu)
-- Rang buoc bao ve bat bien nghiep vu o muc co so du lieu.
-- Yeu cau: MySQL >= 8.0.16 hoac MariaDB >= 10.2 (CHECK moi co tac dung). Kiem tra: SELECT VERSION();
-- May chu InfinityFree dang chay MariaDB 11.4; XAMPP cuc bo MariaDB 10.4 - ca hai deu dung duoc.
-- Chay MOT lan tren CSDL dang chay. CSDL moi dung tu schema.sql thi gop noi dung nay vao schema.sql.
-- BUOC 0: chay cac truy van kiem tra o doi_soat.sql, phan "TRUOC KHI ALTER". Phai ra 0 dong.
-- ============================================================
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1) Rang buoc don gian
-- ------------------------------------------------------------
-- Moi thanh vien hoi dong chi cham 1 lan cho moi de tai
ALTER TABLE `diem_nghiem_thu`
  ADD CONSTRAINT `uq_diem_detai_hoidong` UNIQUE (`de_tai_id`, `hoi_dong_id`);

-- Moc khong trung ten trong cung lop; co 0/1 chi nhan 0 hoac 1
ALTER TABLE `moc_thoi_gian`
  ADD CONSTRAINT `uq_moc_lop_ten` UNIQUE (`lop_hoc_phan_id`, `ten_moc`),
  ADD CONSTRAINT `chk_moc_batbuoc` CHECK (`bat_buoc` IN (0,1));

ALTER TABLE `nguoi_dung` ADD CONSTRAINT `chk_nd_bikhoa` CHECK (`bi_khoa` IN (0,1));

-- Luong 3 them trang thai 'accepted' (da nghiem thu); 'completed' nghia la du dieu kien nghiem thu
ALTER TABLE `de_tai` DROP CONSTRAINT `chk_detai_trangthai`;
ALTER TABLE `de_tai` ADD CONSTRAINT `chk_detai_trangthai`
  CHECK (`trang_thai` IN ('pending','approved','rejected','in_progress','completed','accepted'));
ALTER TABLE `thong_bao`  ADD CONSTRAINT `chk_tb_dadoc`  CHECK (`da_doc` IN (0,1));

-- De tai da qua buoc cho duyet thi phai co GVHD
ALTER TABLE `de_tai`
  ADD CONSTRAINT `chk_detai_gvhd`
  CHECK (`trang_thai` IN ('pending','rejected') OR `gvhd_id` IS NOT NULL);

-- Moi de tai chi co 1 leader (UNIQUE tren cot sinh; NULL khong bi tinh trung)
ALTER TABLE `thanh_vien_nhom`
  ADD COLUMN `leader_de_tai_id` BIGINT UNSIGNED
      AS (IF(`vai_tro_nhom` = 'leader', `de_tai_id`, NULL)) VIRTUAL,
  ADD UNIQUE KEY `uq_tvn_one_leader` (`leader_de_tai_id`);

-- ------------------------------------------------------------
-- 2) Bang khai bao chuyen trang thai hop le (T3: khai bao du lieu)
--    DOI CHIEU VOI bang TRANSITIONS cua V3 truoc khi chot.
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `chuyen_trang_thai_hop_le`;
CREATE TABLE `chuyen_trang_thai_hop_le` (
  `doi_tuong`  VARCHAR(30) NOT NULL,
  `tu_trang_thai` VARCHAR(50) NOT NULL,
  `den_trang_thai` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`doi_tuong`, `tu_trang_thai`, `den_trang_thai`),
  CONSTRAINT `chk_cttht_doituong` CHECK (`doi_tuong` IN ('de_tai','bao_cao_moc'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KHOP VOI V3 (DeTaiService::CHUYEN_TRANG_THAI va BaoCaoMocService::CHUYEN_TRANG_THAI).
-- Chi liet ke cac buoc chuyen ma code V3 THUC SU doi trang_thai bang UPDATE.
-- Khi V3 them buoc moi (vi du approved -> in_progress) thi THEM 1 DONG vao day.
INSERT INTO `chuyen_trang_thai_hop_le` (`doi_tuong`,`tu_trang_thai`,`den_trang_thai`) VALUES
  ('de_tai','pending','approved'),       -- GVHD duyet
  ('de_tai','pending','rejected'),       -- GVHD tu choi
  ('de_tai','rejected','pending'),       -- sinh vien sua lai de tai bi tu choi (hanh dong 'sua')
  ('de_tai','approved','in_progress'),   -- nhom nop ban bao cao dau tien (hanh dong 'bat_dau')
  ('de_tai','in_progress','completed'),  -- moi moc bat buoc da duoc duyet (hanh dong 'du_dieu_kien')
  ('de_tai','completed','accepted'),     -- thu ky khoa chot nghiem thu (hanh dong 'chot_nghiem_thu')
  ('bao_cao_moc','pending','approved'),            -- GVHD duyet bao cao
  ('bao_cao_moc','pending','revision_requested');  -- GVHD yeu cau bo sung
-- Luu y: 'nop_lai' cua bao cao KHONG la UPDATE. V3 tao BAN GHI MOI (pending), ban cu van la
-- revision_requested de giu lich su nhan xet, nen khong can dong revision_requested -> pending.

-- ------------------------------------------------------------
-- 3) Lich su chuyen trang thai: V3 ghi vao bang co san `nhat_ky_he_thong` (khong dung bang rieng).
--    Viec cua V2: bao ve bang do o MUC CO SO DU LIEU - chi duoc ghi them, khong sua/xoa.
--    (Repository cua V3 chi chan o tang ung dung; trigger chan ca khi ai do vao thang CSDL.)
--    Tac dong: khong the DELETE/UPDATE hang trong nhat_ky_he_thong. Neu seed.sql co lenh
--    DELETE FROM nhat_ky_he_thong thi doi sang TRUNCATE (TRUNCATE khong kich hoat trigger DELETE).
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- 4) Trigger. Neu import qua phpMyAdmin bi loi DELIMITER:
--    dan tung trigger vao tab SQL va dat o "Delimiter" thanh $$
-- ------------------------------------------------------------
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
