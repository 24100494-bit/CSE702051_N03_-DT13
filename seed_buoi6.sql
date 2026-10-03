-- ============================================================
-- seed_buoi6.sql (V2) - du lieu mau cho V4 chay kiem thu dong thoi.
-- Dung subquery lay id co san, KHONG gia dinh id cu the. Chay nhieu lan khong tao trung.
-- Truoc khi chay: CSDL phai co lop_hoc_phan, mot tai khoan GVHD va mot tai khoan SINH_VIEN (xem seed.sql).
-- Chi dung du lieu gia lap, khong dung du lieu ca nhan that.
-- ============================================================
SET NAMES utf8mb4;

-- NOW() trong cac moc ben duoi phai la gio Viet Nam (ung dung chay Asia/Ho_Chi_Minh).
-- phpMyAdmin tren InfinityFree mac dinh UTC-7; khong dat dong nay thi moc "sat han" da qua han tu 13 tieng truoc.
SET time_zone = '+07:00';

-- GVHD duoc gan cho de tai thu dong thoi: tai khoan GVHD dau tien con hoat dong (tu dong, khong can dien tay).
-- V3 chi cho GVHD DUOC GAN cho de tai duyet; de gvhd_id = NULL thi ca 50 yeu cau deu bi 403.
SET @gvhd_id = (SELECT nd.id FROM nguoi_dung nd
                  JOIN nguoi_dung_vai_tro x ON x.nguoi_dung_id = nd.id
                  JOIN vai_tro v ON v.id = x.vai_tro_id
                 WHERE v.ten_vai_tro = 'GVHD' AND nd.bi_khoa = 0
                 ORDER BY nd.id LIMIT 1);
SET @vt_sinh_vien = (SELECT id FROM vai_tro WHERE ten_vai_tro = 'SINH_VIEN');

-- Hai sinh vien demo rieng lam nguoi de xuat / nhom truong (khong dung tai khoan thu ky).
-- Mat khau giong tai khoan sinh vien mau dau tien trong seed.sql.
INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau_hash, ho_ten, email)
SELECT 'sv_demo_dong_thoi_1',
       (SELECT nd.mat_khau_hash FROM nguoi_dung nd JOIN nguoi_dung_vai_tro x ON x.nguoi_dung_id = nd.id
         WHERE x.vai_tro_id = @vt_sinh_vien ORDER BY nd.id LIMIT 1),
       'SV demo đồng thời 1', 'sv_demo_dong_thoi_1@st.truong.test'
WHERE NOT EXISTS (SELECT 1 FROM nguoi_dung WHERE ten_dang_nhap = 'sv_demo_dong_thoi_1');

INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau_hash, ho_ten, email)
SELECT 'sv_demo_dong_thoi_2',
       (SELECT nd.mat_khau_hash FROM nguoi_dung nd JOIN nguoi_dung_vai_tro x ON x.nguoi_dung_id = nd.id
         WHERE x.vai_tro_id = @vt_sinh_vien ORDER BY nd.id LIMIT 1),
       'SV demo đồng thời 2', 'sv_demo_dong_thoi_2@st.truong.test'
WHERE NOT EXISTS (SELECT 1 FROM nguoi_dung WHERE ten_dang_nhap = 'sv_demo_dong_thoi_2');

INSERT IGNORE INTO nguoi_dung_vai_tro (nguoi_dung_id, vai_tro_id)
SELECT id, @vt_sinh_vien FROM nguoi_dung WHERE ten_dang_nhap IN ('sv_demo_dong_thoi_1', 'sv_demo_dong_thoi_2');

SET @sv1 = (SELECT id FROM nguoi_dung WHERE ten_dang_nhap = 'sv_demo_dong_thoi_1');
SET @sv2 = (SELECT id FROM nguoi_dung WHERE ten_dang_nhap = 'sv_demo_dong_thoi_2');

-- Ca 1: de tai 'pending' da gan GVHD de thu 50 yeu cau duyet cung luc (mong doi 1x200, 49x422)
INSERT INTO de_tai (ten_de_tai, mo_ta_pham_vi, trang_thai, sinh_vien_de_xuat_id, gvhd_id, lop_hoc_phan_id)
SELECT 'DEMO-DONG-THOI: de tai cho duyet', 'Du lieu mau cho kiem thu dong thoi', 'pending',
       @sv1, @gvhd_id,
       (SELECT id FROM lop_hoc_phan ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM de_tai WHERE ten_de_tai = 'DEMO-DONG-THOI: de tai cho duyet');

INSERT IGNORE INTO thanh_vien_nhom (de_tai_id, sinh_vien_id, vai_tro_nhom)
SELECT (SELECT id FROM de_tai WHERE ten_de_tai = 'DEMO-DONG-THOI: de tai cho duyet'), @sv1, 'leader';

-- Ca 2: de tai co nhom con dung 1 cho (gia dinh toi da 5 thanh vien -> 4 thanh vien hien co)
--       Can them tay 3 sinh vien nua vao thanh_vien_nhom sau khi xem danh sach nguoi_dung trong seed.sql.
INSERT INTO de_tai (ten_de_tai, mo_ta_pham_vi, trang_thai, sinh_vien_de_xuat_id, gvhd_id, lop_hoc_phan_id)
SELECT 'DEMO-DONG-THOI: nhom con 1 cho', 'Thu 2 sinh vien cung vao nhom', 'pending',
       @sv2, NULL,
       (SELECT id FROM lop_hoc_phan ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM de_tai WHERE ten_de_tai = 'DEMO-DONG-THOI: nhom con 1 cho');

INSERT IGNORE INTO thanh_vien_nhom (de_tai_id, sinh_vien_id, vai_tro_nhom)
SELECT (SELECT id FROM de_tai WHERE ten_de_tai = 'DEMO-DONG-THOI: nhom con 1 cho'), @sv2, 'leader';

-- Ca 3: moc sat han (han nop = 1 gio nua, gio Viet Nam) de thu bao cao nop sat gio / qua han
INSERT INTO moc_thoi_gian (lop_hoc_phan_id, ten_moc, mo_ta, han_nop, bat_buoc)
SELECT (SELECT id FROM lop_hoc_phan ORDER BY id LIMIT 1),
       'DEMO: moc sat han', 'Du lieu mau', DATE_ADD(NOW(), INTERVAL 1 HOUR), 1
WHERE NOT EXISTS (SELECT 1 FROM moc_thoi_gian WHERE ten_moc = 'DEMO: moc sat han');

-- Ca 4: moc da qua han 1 ngay (thu danh dau 'overdue')
INSERT INTO moc_thoi_gian (lop_hoc_phan_id, ten_moc, mo_ta, han_nop, bat_buoc)
SELECT (SELECT id FROM lop_hoc_phan ORDER BY id LIMIT 1),
       'DEMO: moc da qua han', 'Du lieu mau', DATE_SUB(NOW(), INTERVAL 1 DAY), 1
WHERE NOT EXISTS (SELECT 1 FROM moc_thoi_gian WHERE ten_moc = 'DEMO: moc da qua han');
