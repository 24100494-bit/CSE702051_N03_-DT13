-- ============================================================
-- verify_restore.sql - Kiem chung CSDL vua phuc hoi tu ban sao luu (Anh 33)
-- Chay TRONG CSDL thu vua phuc hoi (restore_test.bat / restore_test.sh tu chon CSDL nay).
-- Khong can CSDL goc nen dung duoc cho ca ban ket xuat truc tuyen (InfinityFree) lan ban sao luu cuc bo.
--
-- Doc ket qua:
--   Muc 1: so ban ghi tung bang -> doi chieu voi anh chup dem_ban_ghi.sql tren CSDL goc ngay truoc khi ket xuat.
--   Muc 2: moi dong phai la DAT (13 bang, 16 khoa ngoai, 11 rang buoc CHECK); so trigger va chi muc chi de tham khao.
--   Muc 3, 4: moi dong phai bang 0.
--   Muc 5: ban ket xuat tu InfinityFree KHONG co trigger (nen tang chan lenh TRIGGER, #1142) -> toan KHONG la binh thuong;
--          ban du phong cuc bo (backup_db) phai toan CO.
-- Luoc do doi chieu: schema.sql + cap_nhat_csdl_buoi5/6/8.sql (dung luoc do dang chay tren web).
-- Tuong thich MariaDB 10.4 / 11.4 va MySQL 8.0.16+.
-- ============================================================

-- ------------------------------------------------------------
-- 1) So ban ghi tung bang trong CSDL phuc hoi
-- ------------------------------------------------------------
SELECT 'nguoi_dung' AS bang, COUNT(*) AS so_ban_ghi_phuc_hoi FROM nguoi_dung
UNION ALL SELECT 'vai_tro', COUNT(*) FROM vai_tro
UNION ALL SELECT 'nguoi_dung_vai_tro', COUNT(*) FROM nguoi_dung_vai_tro
UNION ALL SELECT 'lop_hoc_phan', COUNT(*) FROM lop_hoc_phan
UNION ALL SELECT 'de_tai', COUNT(*) FROM de_tai
UNION ALL SELECT 'thanh_vien_nhom', COUNT(*) FROM thanh_vien_nhom
UNION ALL SELECT 'moc_thoi_gian', COUNT(*) FROM moc_thoi_gian
UNION ALL SELECT 'bao_cao_moc', COUNT(*) FROM bao_cao_moc
UNION ALL SELECT 'diem_nghiem_thu', COUNT(*) FROM diem_nghiem_thu
UNION ALL SELECT 'nhat_ky_he_thong', COUNT(*) FROM nhat_ky_he_thong
UNION ALL SELECT 'thong_bao', COUNT(*) FROM thong_bao
UNION ALL SELECT 'chuyen_trang_thai_hop_le', COUNT(*) FROM chuyen_trang_thai_hop_le
UNION ALL SELECT 'ma_dat_lai_mat_khau', COUNT(*) FROM ma_dat_lai_mat_khau;

-- ------------------------------------------------------------
-- 2) Cau truc: so bang, khoa ngoai, rang buoc CHECK, trigger (so chi muc chi de tham khao)
-- ------------------------------------------------------------
SELECT hang_muc, phuc_hoi, ky_vong, IF(ky_vong IS NULL, '-', IF(phuc_hoi = ky_vong, 'DAT', 'THIEU')) AS danh_gia
FROM (
  SELECT 'bang' AS hang_muc,
         (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE') AS phuc_hoi, 13 AS ky_vong
  UNION ALL SELECT 'khoa_ngoai',
         (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()), 16
  UNION ALL SELECT 'rang_buoc_check',
         (SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()), 11
  UNION ALL SELECT 'trigger (tham khao: ban web 0, ban cuc bo 5)',
         (SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()), NULL
  UNION ALL SELECT 'chi_muc (ca PRIMARY, tham khao)',
         (SELECT COUNT(DISTINCT TABLE_NAME, INDEX_NAME) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()), NULL
) AS cau_truc;

-- ------------------------------------------------------------
-- 3) Khoa ngoai mo coi (16 khoa ngoai, moi dong phai = 0)
-- ------------------------------------------------------------
SELECT 'nguoi_dung_vai_tro.nguoi_dung_id' AS khoa_ngoai, COUNT(*) AS so_dong_mo_coi
  FROM nguoi_dung_vai_tro c LEFT JOIN nguoi_dung p ON p.id = c.nguoi_dung_id WHERE p.id IS NULL
UNION ALL SELECT 'nguoi_dung_vai_tro.vai_tro_id', COUNT(*)
  FROM nguoi_dung_vai_tro c LEFT JOIN vai_tro p ON p.id = c.vai_tro_id WHERE p.id IS NULL
UNION ALL SELECT 'de_tai.sinh_vien_de_xuat_id', COUNT(*)
  FROM de_tai c LEFT JOIN nguoi_dung p ON p.id = c.sinh_vien_de_xuat_id WHERE p.id IS NULL
UNION ALL SELECT 'de_tai.gvhd_id', COUNT(*)
  FROM de_tai c LEFT JOIN nguoi_dung p ON p.id = c.gvhd_id WHERE c.gvhd_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'de_tai.lop_hoc_phan_id', COUNT(*)
  FROM de_tai c LEFT JOIN lop_hoc_phan p ON p.id = c.lop_hoc_phan_id WHERE p.id IS NULL
UNION ALL SELECT 'thanh_vien_nhom.de_tai_id', COUNT(*)
  FROM thanh_vien_nhom c LEFT JOIN de_tai p ON p.id = c.de_tai_id WHERE p.id IS NULL
UNION ALL SELECT 'thanh_vien_nhom.sinh_vien_id', COUNT(*)
  FROM thanh_vien_nhom c LEFT JOIN nguoi_dung p ON p.id = c.sinh_vien_id WHERE p.id IS NULL
UNION ALL SELECT 'moc_thoi_gian.lop_hoc_phan_id', COUNT(*)
  FROM moc_thoi_gian c LEFT JOIN lop_hoc_phan p ON p.id = c.lop_hoc_phan_id WHERE p.id IS NULL
UNION ALL SELECT 'bao_cao_moc.de_tai_id', COUNT(*)
  FROM bao_cao_moc c LEFT JOIN de_tai p ON p.id = c.de_tai_id WHERE p.id IS NULL
UNION ALL SELECT 'bao_cao_moc.moc_thoi_gian_id', COUNT(*)
  FROM bao_cao_moc c LEFT JOIN moc_thoi_gian p ON p.id = c.moc_thoi_gian_id WHERE p.id IS NULL
UNION ALL SELECT 'diem_nghiem_thu.de_tai_id', COUNT(*)
  FROM diem_nghiem_thu c LEFT JOIN de_tai p ON p.id = c.de_tai_id WHERE p.id IS NULL
UNION ALL SELECT 'diem_nghiem_thu.hoi_dong_id', COUNT(*)
  FROM diem_nghiem_thu c LEFT JOIN nguoi_dung p ON p.id = c.hoi_dong_id WHERE p.id IS NULL
UNION ALL SELECT 'nhat_ky_he_thong.nguoi_dung_id', COUNT(*)
  FROM nhat_ky_he_thong c LEFT JOIN nguoi_dung p ON p.id = c.nguoi_dung_id WHERE c.nguoi_dung_id IS NOT NULL AND p.id IS NULL
UNION ALL SELECT 'thong_bao.nguoi_dung_id', COUNT(*)
  FROM thong_bao c LEFT JOIN nguoi_dung p ON p.id = c.nguoi_dung_id WHERE p.id IS NULL
UNION ALL SELECT 'ma_dat_lai_mat_khau.nguoi_dung_id', COUNT(*)
  FROM ma_dat_lai_mat_khau c LEFT JOIN nguoi_dung p ON p.id = c.nguoi_dung_id WHERE p.id IS NULL
UNION ALL SELECT 'ma_dat_lai_mat_khau.tao_boi', COUNT(*)
  FROM ma_dat_lai_mat_khau c LEFT JOIN nguoi_dung p ON p.id = c.tao_boi WHERE c.tao_boi IS NOT NULL AND p.id IS NULL;

-- ------------------------------------------------------------
-- 4) Du lieu ngoai mien gia tri (theo 11 rang buoc CHECK cua luoc do; moi dong phai = 0)
-- ------------------------------------------------------------
SELECT 'diem_nghiem_thu.diem ngoai 0..10' AS kiem_tra, COUNT(*) AS so_dong_sai
  FROM diem_nghiem_thu WHERE diem < 0 OR diem > 10
UNION ALL SELECT 'de_tai.trang_thai khong hop le', COUNT(*)
  FROM de_tai WHERE trang_thai NOT IN ('pending','approved','rejected','in_progress','completed','accepted')
UNION ALL SELECT 'de_tai da duyet tro len ma chua co GVHD', COUNT(*)
  FROM de_tai WHERE trang_thai NOT IN ('pending','rejected') AND gvhd_id IS NULL
UNION ALL SELECT 'bao_cao_moc.trang_thai khong hop le', COUNT(*)
  FROM bao_cao_moc WHERE trang_thai NOT IN ('pending','approved','revision_requested','overdue')
UNION ALL SELECT 'lop_hoc_phan.hoc_ky khong hop le', COUNT(*)
  FROM lop_hoc_phan WHERE hoc_ky NOT IN ('HK1','HK2','HK3')
UNION ALL SELECT 'thanh_vien_nhom.vai_tro_nhom khong hop le', COUNT(*)
  FROM thanh_vien_nhom WHERE vai_tro_nhom NOT IN ('leader','member')
UNION ALL SELECT 'co 0/1 (bi_khoa, bat_buoc, da_doc, da_dung) khong hop le',
  (SELECT COUNT(*) FROM nguoi_dung WHERE bi_khoa NOT IN (0,1)) + (SELECT COUNT(*) FROM moc_thoi_gian WHERE bat_buoc NOT IN (0,1))
  + (SELECT COUNT(*) FROM thong_bao WHERE da_doc NOT IN (0,1)) + (SELECT COUNT(*) FROM ma_dat_lai_mat_khau WHERE da_dung NOT IN (0,1))
UNION ALL SELECT 'chuyen_trang_thai_hop_le.doi_tuong khong hop le', COUNT(*)
  FROM chuyen_trang_thai_hop_le WHERE doi_tuong NOT IN ('de_tai','bao_cao_moc');

-- ------------------------------------------------------------
-- 5) Trigger cua he thong (chi ban du phong cuc bo moi co; ban web toan KHONG la binh thuong)
-- ------------------------------------------------------------
SELECT t.ten AS trigger_can_co, IF(tr.TRIGGER_NAME IS NULL, 'KHONG', 'CO') AS trang_thai
FROM (SELECT 'trg_baocao_chuyen_trangthai' AS ten UNION ALL SELECT 'trg_detai_chuyen_trangthai'
      UNION ALL SELECT 'trg_nhatky_khong_sua' UNION ALL SELECT 'trg_nhatky_khong_xoa' UNION ALL SELECT 'trg_tvn_toi_da') t
LEFT JOIN information_schema.TRIGGERS tr ON tr.TRIGGER_SCHEMA = DATABASE() AND tr.TRIGGER_NAME = t.ten;
