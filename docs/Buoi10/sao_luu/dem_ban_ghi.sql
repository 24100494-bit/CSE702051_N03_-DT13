-- dem_ban_ghi.sql: dem so ban ghi that (COUNT(*)) cua 13 bang trong CSDL DANG CHON.
-- Khong ghi ten CSDL nen chay duoc o ca hai noi:
--   * phpMyAdmin cua InfinityFree: bam vao CSDL cua nhom (if0_...) -> tab SQL -> dan noi dung tep nay -> Go.
--     Chup man hinh ket qua ngay truoc khi Xuat (Export) = so lieu goc cua ban ket xuat truc tuyen.
--   * XAMPP: backup_db.bat goi tep nay cho CSDL quan_ly_do_an.
-- Khong dung information_schema.TABLE_ROWS vi voi InnoDB do chi la so uoc luong (va InfinityFree chan information_schema).

SELECT 'nguoi_dung' AS bang, COUNT(*) AS so_ban_ghi FROM nguoi_dung
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
