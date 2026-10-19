-- ============================================================
-- so_sanh_voi_goc_cuc_bo.sql - CHI dung cho ban sao luu du phong cuc bo (backup_db.bat).
-- So sanh CSDL phuc hoi quan_ly_do_an_restore_test voi CSDL goc quan_ly_do_an tren cung may:
-- so ban ghi va checksum tung bang phai giong nhau. restore_test.bat chay tep nay khi them tham so "cuc_bo".
-- Chi co y nghia neu CSDL goc khong bi sua giua luc sao luu va luc kiem chung (chay hai script lien tiep).
-- Ban ket xuat truc tuyen thi doi chieu Muc 1 cua verify_restore.sql voi anh chup dem_ban_ghi.sql tren web.
-- ============================================================

SELECT bang, phuc_hoi, goc, IF(phuc_hoi = goc, 'KHOP', 'LECH') AS so_voi_goc
FROM (
  SELECT 'nguoi_dung' AS bang, (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.nguoi_dung) AS phuc_hoi, (SELECT COUNT(*) FROM quan_ly_do_an.nguoi_dung) AS goc
  UNION ALL SELECT 'vai_tro', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.vai_tro), (SELECT COUNT(*) FROM quan_ly_do_an.vai_tro)
  UNION ALL SELECT 'nguoi_dung_vai_tro', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.nguoi_dung_vai_tro), (SELECT COUNT(*) FROM quan_ly_do_an.nguoi_dung_vai_tro)
  UNION ALL SELECT 'lop_hoc_phan', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.lop_hoc_phan), (SELECT COUNT(*) FROM quan_ly_do_an.lop_hoc_phan)
  UNION ALL SELECT 'de_tai', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.de_tai), (SELECT COUNT(*) FROM quan_ly_do_an.de_tai)
  UNION ALL SELECT 'thanh_vien_nhom', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.thanh_vien_nhom), (SELECT COUNT(*) FROM quan_ly_do_an.thanh_vien_nhom)
  UNION ALL SELECT 'moc_thoi_gian', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.moc_thoi_gian), (SELECT COUNT(*) FROM quan_ly_do_an.moc_thoi_gian)
  UNION ALL SELECT 'bao_cao_moc', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.bao_cao_moc), (SELECT COUNT(*) FROM quan_ly_do_an.bao_cao_moc)
  UNION ALL SELECT 'diem_nghiem_thu', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.diem_nghiem_thu), (SELECT COUNT(*) FROM quan_ly_do_an.diem_nghiem_thu)
  UNION ALL SELECT 'nhat_ky_he_thong', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.nhat_ky_he_thong), (SELECT COUNT(*) FROM quan_ly_do_an.nhat_ky_he_thong)
  UNION ALL SELECT 'thong_bao', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.thong_bao), (SELECT COUNT(*) FROM quan_ly_do_an.thong_bao)
  UNION ALL SELECT 'chuyen_trang_thai_hop_le', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.chuyen_trang_thai_hop_le), (SELECT COUNT(*) FROM quan_ly_do_an.chuyen_trang_thai_hop_le)
  UNION ALL SELECT 'ma_dat_lai_mat_khau', (SELECT COUNT(*) FROM quan_ly_do_an_restore_test.ma_dat_lai_mat_khau), (SELECT COUNT(*) FROM quan_ly_do_an.ma_dat_lai_mat_khau)
) AS dem;

-- Checksum noi dung: tung cap (goc, phuc hoi) nam canh nhau, hai gia tri phai BANG NHAU
-- (thanh_vien_nhom co cot sinh VIRTUAL leader_de_tai_id: CHECKSUM TABLE cua MariaDB cho ket qua khac nhau du du lieu giong het,
--  nen bang nay so bang CRC32 tren cac cot that o cau lenh ngay sau)
CHECKSUM TABLE
  quan_ly_do_an.nguoi_dung,               quan_ly_do_an_restore_test.nguoi_dung,
  quan_ly_do_an.vai_tro,                  quan_ly_do_an_restore_test.vai_tro,
  quan_ly_do_an.nguoi_dung_vai_tro,       quan_ly_do_an_restore_test.nguoi_dung_vai_tro,
  quan_ly_do_an.lop_hoc_phan,             quan_ly_do_an_restore_test.lop_hoc_phan,
  quan_ly_do_an.de_tai,                   quan_ly_do_an_restore_test.de_tai,
  quan_ly_do_an.moc_thoi_gian,            quan_ly_do_an_restore_test.moc_thoi_gian,
  quan_ly_do_an.bao_cao_moc,              quan_ly_do_an_restore_test.bao_cao_moc,
  quan_ly_do_an.diem_nghiem_thu,          quan_ly_do_an_restore_test.diem_nghiem_thu,
  quan_ly_do_an.nhat_ky_he_thong,         quan_ly_do_an_restore_test.nhat_ky_he_thong,
  quan_ly_do_an.thong_bao,                quan_ly_do_an_restore_test.thong_bao,
  quan_ly_do_an.chuyen_trang_thai_hop_le, quan_ly_do_an_restore_test.chuyen_trang_thai_hop_le,
  quan_ly_do_an.ma_dat_lai_mat_khau,      quan_ly_do_an_restore_test.ma_dat_lai_mat_khau;

SELECT 'thanh_vien_nhom (CRC32 cot that)' AS bang,
       (SELECT SUM(CRC32(CONCAT_WS('|', de_tai_id, sinh_vien_id, vai_tro_nhom))) FROM quan_ly_do_an.thanh_vien_nhom) AS goc,
       (SELECT SUM(CRC32(CONCAT_WS('|', de_tai_id, sinh_vien_id, vai_tro_nhom))) FROM quan_ly_do_an_restore_test.thanh_vien_nhom) AS phuc_hoi,
       IF((SELECT SUM(CRC32(CONCAT_WS('|', de_tai_id, sinh_vien_id, vai_tro_nhom))) FROM quan_ly_do_an.thanh_vien_nhom)
        = (SELECT SUM(CRC32(CONCAT_WS('|', de_tai_id, sinh_vien_id, vai_tro_nhom))) FROM quan_ly_do_an_restore_test.thanh_vien_nhom), 'KHOP', 'LECH') AS so_voi_goc;
