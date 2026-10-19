# Từ điển dữ liệu — CSDL `quan_ly_do_an`

Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu (đề tài DT13) – Nhóm 13, học phần CSE702051. Lược đồ đầy đủ = `schema.sql` + `cap_nhat_csdl_buoi5.sql`, `cap_nhat_csdl_buoi6.sql`, `cap_nhat_csdl_buoi8.sql`; chỉ mục bổ sung ở `indexes_v2.sql`, `indexes_search.sql`, `indexes_buoi09.sql`. Bảng dưới được sinh từ CSDL đã nạp đủ các tệp trên (MariaDB 10.4); cột "Mô tả" do nhóm soạn.

**Tổng quan:** 13 bảng, 83 cột, 16 khóa ngoại, 11 ràng buộc CHECK, 5 trigger, 52 chỉ mục (kể cả khóa chính). Engine InnoDB, bộ ký tự utf8mb4, collation utf8mb4_unicode_ci.

## Bảng tổng hợp

| STT | Bảng | Mô tả | Số cột | Số bản ghi mẫu |
|---:|---|---|---:|---:|
| 1 | `nguoi_dung` | Tài khoản người dùng | 9 | 223 |
| 2 | `vai_tro` | Danh mục vai trò | 5 | 4 |
| 3 | `nguoi_dung_vai_tro` | Gán vai trò cho người dùng (nhiều – nhiều) | 2 | 223 |
| 4 | `lop_hoc_phan` | Lớp học phần | 7 | 5 |
| 5 | `de_tai` | Đề tài | 9 | 83 |
| 6 | `thanh_vien_nhom` | Thành viên nhóm thực hiện đề tài | 4 | 204 |
| 7 | `moc_thoi_gian` | Mốc nộp báo cáo của lớp học phần | 8 | 24 |
| 8 | `bao_cao_moc` | Bản nộp báo cáo theo mốc | 9 | 77 |
| 9 | `diem_nghiem_thu` | Điểm nghiệm thu của từng thành viên hội đồng | 7 | 28 |
| 10 | `chuyen_trang_thai_hop_le` | Bảng chuyển trạng thái hợp lệ | 3 | 8 |
| 11 | `thong_bao` | Thông báo gửi người dùng | 7 | 116 |
| 12 | `nhat_ky_he_thong` | Nhật ký hành vi, chỉ ghi thêm (trigger chặn sửa, xóa) | 6 | 302 |
| 13 | `ma_dat_lai_mat_khau` | Mã đặt lại mật khẩu một lần | 7 | 0 |

Số bản ghi mẫu = `seed.sql` + `seed_extended.sql` sau khi chạy các tệp cập nhật (chưa tính `seed_bulk.sql`, `seed_bulk_buoi9.sql` – chỉ nạp cục bộ để đo hiệu năng). Ký hiệu khóa: PK khóa chính, FK khóa ngoại, UQ duy nhất.

## 1. Bảng `nguoi_dung`

Tài khoản người dùng.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã người dùng. |
| 2 | `ten_dang_nhap` | VARCHAR(50) | Không |  | UQ | Tên đăng nhập. |
| 3 | `mat_khau_hash` | VARCHAR(255) | Không |  |  | Mật khẩu đã băm bcrypt. |
| 4 | `ho_ten` | VARCHAR(100) | Không |  |  | Họ tên. |
| 5 | `email` | VARCHAR(100) | Không |  | UQ | Thư điện tử. |
| 6 | `so_dien_thoai` | VARCHAR(15) | Có | NULL |  | Số điện thoại. |
| 7 | `bi_khoa` | TINYINT(1) | Không | 0 |  | Tài khoản bị khóa. |
| 8 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 9 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_nd_bikhoa` | `bi_khoa in (0,1)` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `email` | Duy nhất | email | schema.sql |
| `idx_nguoidung_hoten` | Thường | ho_ten | indexes_search.sql |
| `ten_dang_nhap` | Duy nhất | ten_dang_nhap | schema.sql |

## 2. Bảng `vai_tro`

Danh mục vai trò.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã vai trò. |
| 2 | `ten_vai_tro` | VARCHAR(50) | Không |  | UQ | THU_KY_KHOA, GVHD, SINH_VIEN, HOI_DONG. |
| 3 | `mo_ta` | VARCHAR(255) | Có | NULL |  | Mô tả vai trò. |
| 4 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 5 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `ten_vai_tro` | Duy nhất | ten_vai_tro | schema.sql |

## 3. Bảng `nguoi_dung_vai_tro`

Gán vai trò cho người dùng (nhiều – nhiều).

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `nguoi_dung_id` | BIGINT(20) UNSIGNED | Không |  | PK, FK → nguoi_dung | Người dùng. |
| 2 | `vai_tro_id` | BIGINT(20) UNSIGNED | Không |  | PK, FK → vai_tro | Vai trò. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `nguoi_dung_id` | `nguoi_dung.id` | xóa theo (CASCADE) |
| `vai_tro_id` | `vai_tro.id` | xóa theo (CASCADE) |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | nguoi_dung_id, vai_tro_id | schema.sql |
| `fk_ndvt_vaitro` | Thường | vai_tro_id | schema.sql |

## 4. Bảng `lop_hoc_phan`

Lớp học phần.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã lớp. |
| 2 | `ma_lop` | VARCHAR(20) | Không |  | UQ | Mã lớp học phần. |
| 3 | `ten_lop` | VARCHAR(100) | Không |  |  | Tên lớp. |
| 4 | `hoc_ky` | VARCHAR(20) | Không |  |  | Học kỳ. |
| 5 | `nam_hoc` | VARCHAR(20) | Không |  |  | Năm học, ví dụ 2026-2027. |
| 6 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 7 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_lophocphan_hocky` | `hoc_ky in ('HK1','HK2','HK3')` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `idx_lophocphan_namhoc_hocky` | Thường | nam_hoc, hoc_ky | indexes_search.sql |
| `ma_lop` | Duy nhất | ma_lop | schema.sql |

## 5. Bảng `de_tai`

Đề tài.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã đề tài. |
| 2 | `ten_de_tai` | VARCHAR(200) | Không |  |  | Tên đề tài, không trùng trong cùng lớp (kiểm ở tầng nghiệp vụ). |
| 3 | `mo_ta_pham_vi` | TEXT | Có | NULL |  | Mô tả phạm vi. |
| 4 | `trang_thai` | VARCHAR(50) | Không | pending |  | pending, approved, rejected, in_progress, completed, accepted. |
| 5 | `sinh_vien_de_xuat_id` | BIGINT(20) UNSIGNED | Không |  | FK → nguoi_dung | Sinh viên đề xuất. |
| 6 | `gvhd_id` | BIGINT(20) UNSIGNED | Có | NULL | FK → nguoi_dung | Giảng viên hướng dẫn; bắt buộc khi đề tài khác pending, rejected (CHECK). |
| 7 | `lop_hoc_phan_id` | BIGINT(20) UNSIGNED | Không |  | FK → lop_hoc_phan | Lớp học phần. |
| 8 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 9 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `gvhd_id` | `nguoi_dung.id` | chặn xóa (RESTRICT) |
| `lop_hoc_phan_id` | `lop_hoc_phan.id` | chặn xóa (RESTRICT) |
| `sinh_vien_de_xuat_id` | `nguoi_dung.id` | chặn xóa (RESTRICT) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_detai_gvhd` | `trang_thai in ('pending','rejected') or gvhd_id is not null` |
| `chk_detai_trangthai` | `trang_thai in ('pending','approved','rejected','in_progress','completed','accepted')` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_detai_gvhd` | Thường | gvhd_id | schema.sql |
| `fk_detai_lophocphan` | Thường | lop_hoc_phan_id | schema.sql |
| `fk_detai_sv` | Thường | sinh_vien_de_xuat_id | schema.sql |
| `idx_detai_created` | Thường | created_at | indexes_search.sql |
| `idx_detai_gvhd_trangthai` | Thường | gvhd_id, trang_thai | indexes_v2.sql |
| `idx_detai_lop_trangthai` | Thường | lop_hoc_phan_id, trang_thai | schema.sql |
| `idx_detai_lop_trangthai_created` | Thường | lop_hoc_phan_id, trang_thai, created_at | indexes_v2.sql |
| `idx_detai_ten` | Thường | ten_de_tai | indexes_search.sql |

**Trigger**

| Tên | Thời điểm | Mục đích |
|---|---|---|
| `trg_detai_chuyen_trangthai` | BEFORE UPDATE | Chặn đổi trạng thái đề tài không có trong bảng chuyen_trang_thai_hop_le |

## 6. Bảng `thanh_vien_nhom`

Thành viên nhóm thực hiện đề tài.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `de_tai_id` | BIGINT(20) UNSIGNED | Không |  | PK, FK → de_tai | Đề tài. |
| 2 | `sinh_vien_id` | BIGINT(20) UNSIGNED | Không |  | PK, FK → nguoi_dung | Sinh viên. |
| 3 | `vai_tro_nhom` | VARCHAR(20) | Không | member |  | Nhóm trưởng hoặc thành viên. |
| 4 | `leader_de_tai_id` | BIGINT(20) UNSIGNED | Có | cột sinh: if(vai_tro_nhom = 'leader',de_tai_id,NULL) | UQ | Bằng de_tai_id khi là nhóm trưởng; bảo đảm mỗi đề tài một nhóm trưởng. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `de_tai_id` | `de_tai.id` | xóa theo (CASCADE) |
| `sinh_vien_id` | `nguoi_dung.id` | chặn xóa (RESTRICT) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_tvn_vaitronhom` | `vai_tro_nhom in ('leader','member')` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | de_tai_id, sinh_vien_id | schema.sql |
| `fk_tvn_sv` | Thường | sinh_vien_id | schema.sql |
| `uq_tvn_one_leader` | Duy nhất | leader_de_tai_id | cap_nhat_csdl_buoi6.sql |

**Trigger**

| Tên | Thời điểm | Mục đích |
|---|---|---|
| `trg_tvn_toi_da` | BEFORE INSERT | Chặn thêm thành viên khi nhóm đã đủ 5 người |

## 7. Bảng `moc_thoi_gian`

Mốc nộp báo cáo của lớp học phần.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã mốc. |
| 2 | `lop_hoc_phan_id` | BIGINT(20) UNSIGNED | Không |  | FK → lop_hoc_phan | Lớp học phần. |
| 3 | `ten_moc` | VARCHAR(100) | Không |  |  | Tên mốc. |
| 4 | `mo_ta` | TEXT | Có | NULL |  | Mô tả. |
| 5 | `han_nop` | DATETIME | Không |  |  | Hạn nộp. |
| 6 | `bat_buoc` | TINYINT(1) | Không | 1 |  | Mốc bắt buộc để đủ điều kiện nghiệm thu. |
| 7 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 8 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `lop_hoc_phan_id` | `lop_hoc_phan.id` | xóa theo (CASCADE) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_moc_batbuoc` | `bat_buoc in (0,1)` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_mocthoigian_lop` | Thường | lop_hoc_phan_id | schema.sql |
| `idx_mocthoigian_hannop` | Thường | han_nop | indexes_v2.sql |
| `idx_mocthoigian_lop_hannop` | Thường | lop_hoc_phan_id, han_nop | schema.sql |
| `uq_moc_lop_ten` | Duy nhất | lop_hoc_phan_id, ten_moc | cap_nhat_csdl_buoi6.sql |

## 8. Bảng `bao_cao_moc`

Bản nộp báo cáo theo mốc.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã bản nộp. |
| 2 | `de_tai_id` | BIGINT(20) UNSIGNED | Không |  | FK → de_tai | Đề tài. |
| 3 | `moc_thoi_gian_id` | BIGINT(20) UNSIGNED | Không |  | FK → moc_thoi_gian | Mốc. |
| 4 | `duong_dan_tep` | VARCHAR(500) | Không |  |  | Đường dẫn tệp lưu ngoài thư mục web, tên đã đổi. |
| 5 | `ten_tep_goc` | VARCHAR(255) | Không |  |  | Tên tệp người dùng tải lên. |
| 6 | `trang_thai` | VARCHAR(50) | Không | pending |  | pending, approved, revision_requested, overdue. |
| 7 | `nhan_xet_gv` | TEXT | Có | NULL |  | Nhận xét của GVHD. |
| 8 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 9 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `de_tai_id` | `de_tai.id` | xóa theo (CASCADE) |
| `moc_thoi_gian_id` | `moc_thoi_gian.id` | chặn xóa (RESTRICT) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_baocao_trangthai` | `trang_thai in ('pending','approved','revision_requested','overdue')` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_baocao_detai` | Thường | de_tai_id | schema.sql |
| `fk_baocao_moc` | Thường | moc_thoi_gian_id | schema.sql |
| `idx_baocao_detai_moc_created_id` | Thường | de_tai_id, moc_thoi_gian_id, created_at, id | schema.sql (CSDL cũ: indexes_buoi09.sql) |
| `idx_baocao_detai_trangthai_created` | Thường | de_tai_id, trang_thai, created_at | schema.sql |
| `idx_baocao_moc_trangthai` | Thường | moc_thoi_gian_id, trang_thai | indexes_v2.sql |

**Trigger**

| Tên | Thời điểm | Mục đích |
|---|---|---|
| `trg_baocao_chuyen_trangthai` | BEFORE UPDATE | Chặn đổi trạng thái báo cáo mốc không có trong bảng chuyen_trang_thai_hop_le |

## 9. Bảng `diem_nghiem_thu`

Điểm nghiệm thu của từng thành viên hội đồng.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã lượt chấm. |
| 2 | `de_tai_id` | BIGINT(20) UNSIGNED | Không |  | FK → de_tai | Đề tài. |
| 3 | `hoi_dong_id` | BIGINT(20) UNSIGNED | Không |  | FK → nguoi_dung | Thành viên hội đồng. |
| 4 | `diem` | DECIMAL(4,2) | Không |  |  | Điểm. |
| 5 | `nhan_xet` | TEXT | Có | NULL |  | Nhận xét. |
| 6 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 7 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `de_tai_id` | `de_tai.id` | chặn xóa (RESTRICT) |
| `hoi_dong_id` | `nguoi_dung.id` | chặn xóa (RESTRICT) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_diem_khoangdiem` | `diem >= 0 and diem <= 10` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_diem_detai` | Thường | de_tai_id | schema.sql |
| `fk_diem_hoidong` | Thường | hoi_dong_id | schema.sql |
| `idx_diem_detai_diem` | Thường | de_tai_id, diem | indexes_v2.sql |
| `uq_diem_detai_hoidong` | Duy nhất | de_tai_id, hoi_dong_id | cap_nhat_csdl_buoi6.sql |

## 10. Bảng `chuyen_trang_thai_hop_le`

Bảng chuyển trạng thái hợp lệ.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `doi_tuong` | VARCHAR(30) | Không |  | PK | Loại đối tượng. |
| 2 | `tu_trang_thai` | VARCHAR(50) | Không |  | PK | Trạng thái nguồn. |
| 3 | `den_trang_thai` | VARCHAR(50) | Không |  | PK | Trạng thái đích. |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_cttht_doituong` | `doi_tuong in ('de_tai','bao_cao_moc')` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | doi_tuong, tu_trang_thai, den_trang_thai | schema.sql |

## 11. Bảng `thong_bao`

Thông báo gửi người dùng.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã thông báo. |
| 2 | `nguoi_dung_id` | BIGINT(20) UNSIGNED | Không |  | FK → nguoi_dung | Người nhận. |
| 3 | `tieu_de` | VARCHAR(200) | Không |  |  | Tiêu đề. |
| 4 | `noi_dung` | TEXT | Không |  |  | Nội dung. |
| 5 | `da_doc` | TINYINT(1) | Không | 0 |  | Đã đọc. |
| 6 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |
| 7 | `updated_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP (tự cập nhật) |  | Thời điểm cập nhật gần nhất, tự cập nhật. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `nguoi_dung_id` | `nguoi_dung.id` | xóa theo (CASCADE) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_tb_dadoc` | `da_doc in (0,1)` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_thongbao_nguoidung` | Thường | nguoi_dung_id | schema.sql |
| `idx_thongbao_nguoidung_dadoc_created` | Thường | nguoi_dung_id, da_doc, created_at | schema.sql |

## 12. Bảng `nhat_ky_he_thong`

Nhật ký hành vi, chỉ ghi thêm (trigger chặn sửa, xóa).

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã dòng nhật ký. |
| 2 | `nguoi_dung_id` | BIGINT(20) UNSIGNED | Có | NULL | FK → nguoi_dung | Người thực hiện. |
| 3 | `hanh_dong` | VARCHAR(100) | Không |  |  | Mã hành động, ví dụ LOGIN_SUCCESS, APPROVE_TOPIC. |
| 4 | `chi_tiet` | TEXT | Có | NULL |  | Mô tả chi tiết. |
| 5 | `dia_chi_ip` | VARCHAR(45) | Có | NULL |  | Địa chỉ IP. |
| 6 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `nguoi_dung_id` | `nguoi_dung.id` | đặt NULL (SET NULL) |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_nhatky_nguoidung` | Thường | nguoi_dung_id | schema.sql |
| `idx_nhatky_created` | Thường | created_at | indexes_search.sql |
| `idx_nhatky_hanhdong_created` | Thường | hanh_dong, created_at | schema.sql |
| `idx_nhatky_nguoidung_created` | Thường | nguoi_dung_id, created_at | indexes_v2.sql |

**Trigger**

| Tên | Thời điểm | Mục đích |
|---|---|---|
| `trg_nhatky_khong_sua` | BEFORE UPDATE | Nhật ký chỉ ghi thêm: chặn mọi lệnh UPDATE |
| `trg_nhatky_khong_xoa` | BEFORE DELETE | Nhật ký chỉ ghi thêm: chặn mọi lệnh DELETE |

## 13. Bảng `ma_dat_lai_mat_khau`

Mã đặt lại mật khẩu một lần.

| STT | Cột | Kiểu dữ liệu | Null | Mặc định | Khóa | Mô tả |
|---:|---|---|---|---|---|---|
| 1 | `id` | BIGINT(20) UNSIGNED | Không | tự tăng | PK | Mã. |
| 2 | `nguoi_dung_id` | BIGINT(20) UNSIGNED | Không |  | FK → nguoi_dung | Tài khoản được đặt lại. |
| 3 | `ma_hash` | CHAR(64) | Không |  | UQ | SHA-256 của mã, không lưu mã gốc. |
| 4 | `het_han` | DATETIME | Không |  |  | Hết hạn sau 15 phút. |
| 5 | `da_dung` | TINYINT(1) | Không | 0 |  | Đã dùng. |
| 6 | `tao_boi` | BIGINT(20) UNSIGNED | Có | NULL | FK → nguoi_dung | Thư ký khoa cấp mã. |
| 7 | `created_at` | TIMESTAMP | Có | CURRENT_TIMESTAMP |  | Thời điểm tạo bản ghi. |

**Khóa ngoại**

| Cột | Tham chiếu | Khi xóa bản ghi cha |
|---|---|---|
| `nguoi_dung_id` | `nguoi_dung.id` | xóa theo (CASCADE) |
| `tao_boi` | `nguoi_dung.id` | đặt NULL (SET NULL) |

**Ràng buộc CHECK**

| Tên | Điều kiện |
|---|---|
| `chk_madatlai_dadung` | `da_dung in (0,1)` |

**Chỉ mục**

| Tên | Loại | Cột | Nguồn |
|---|---|---|---|
| `PRIMARY` | Khóa chính | id | schema.sql |
| `fk_madatlai_taoboi` | Thường | tao_boi | cap_nhat_csdl_buoi8.sql |
| `idx_madatlai_nguoidung` | Thường | nguoi_dung_id, da_dung | cap_nhat_csdl_buoi8.sql |
| `uq_madatlai_hash` | Duy nhất | ma_hash | cap_nhat_csdl_buoi8.sql |

