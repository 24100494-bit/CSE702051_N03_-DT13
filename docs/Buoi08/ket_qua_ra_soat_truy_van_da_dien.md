# Kết quả rà soát truy vấn (V2 – Phụ trách dữ liệu)

Nhóm: CSE702051_N03_-DT13 · Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu · CSDL `quan_ly_do_an` · Framework: CodeIgniter 4
Người thực hiện: Nguyễn Quốc Khánh (V2) · Ngày rà soát: 09/10/2026 · Commit được rà soát: `acbd7e8` (nhánh `develop`, sau PR #22)

**Tiêu chí đạt:** không có câu SQL nào ghép chuỗi từ dữ liệu đầu vào (`getGet`, `getPost`, `getJSON`, tên tệp tải lên, giá trị đọc lại từ CSDL). Mọi giá trị đi vào truy vấn phải qua tham số ràng buộc (`?` hoặc `:ten:` + mảng tham số) hoặc Query Builder. Tên cột, hướng sắp xếp, `LIMIT` từ đầu vào phải qua whitelist hoặc ép kiểu.

## 1. Phương pháp
1. Chạy `scan_queries.ps1` trên toàn repo (9 mẫu A–I): mẫu A–C bắt chuỗi ghép với `$_GET/$_POST`, biến chèn trong chuỗi SQL và ghép bằng dấu chấm; D bắt `query()` không dùng tham số; E bắt escape thủ công; F bắt `ORDER BY`/`LIMIT`/tên bảng lấy từ biến trong SQL viết tay; I bắt các chỗ riêng của Query Builder CodeIgniter: `orderBy($bien)`, tắt escape (`false`), `like()` nhận biến, `query($sql, ...)`.
2. Vì dự án dùng CodeIgniter 4 (đọc đầu vào bằng `$this->request->getGet()`, không dùng `$_GET`), chạy thêm lượt quét thứ hai theo `getGet|getPost|getVar|getJSON|orderBy|like|select|groupBy|having|, false)|FIELD|escape`.
3. Đọc ngữ cảnh từng dòng nghi vấn và các câu `query(` nhiều dòng mà quét theo dòng không thấy hết; lần theo từng tham số `getGet` từ Controller qua Service xuống Repository.

## 2. Tệp SQL thuần
| Tệp | Có biến/đầu vào không | Kết luận |
|---|---|---|
| `schema.sql`, `indexes_v2.sql`, `indexes_search.sql` | Không (DDL) | ĐẠT |
| `reports_queries.sql` | Không; khoảng ngày dùng biến phiên `@tu_ngay`, `@den_ngay` do người chạy đặt trong phpMyAdmin, không nhận dữ liệu từ ứng dụng | ĐẠT |
| `seed.sql`, `seed_extended.sql`, `seed_bulk.sql`, `seed_buoi6.sql`, `doi_soat.sql`, `cap_nhat_csdl_buoi5.sql`, `cap_nhat_csdl_buoi6.sql` | Không (câu lệnh hằng số) | ĐẠT (xác nhận lại bằng mắt) |

## 3. Mã nguồn ứng dụng
Kết quả quét: các mục A, B, E, F rỗng. Mục C, D, I và lượt quét thứ hai có các dòng sau.

| # | Tệp:dòng | Mẫu | Nội dung / nguồn dữ liệu | Kết luận |
|---|---|---|---|---|
| 1 | `NhatKyHeThongRepository.php:63` | C, I | `REGEXP` ghép `$mau`. `$mau` luôn là `'de tai ID: ' . $deTaiId` hoặc `'bao cao ID: ' . $baoCaoId` với tham số khai báo kiểu `int` (dòng 44, 50); kết quả còn qua `$this->db->escape()` | ĐẠT (không có ký tự do người dùng điều khiển) |
| 2 | `LopHocPhanService.php:65`, `MocThoiGianService.php:64` | C | Ghép chuỗi để ghi nhật ký, không phải câu SQL; hàm `ghiNhan()` lưu bằng Query Builder `insert()` | ĐẠT (báo nhầm) |
| 3 | `BaoCaoMocRepository.php:43`, `DeTaiRepository.php:162, 223, 224`, `LopHocPhanRepository.php:55` | D | `query('... WHERE id = ? FOR UPDATE', [$id])` dùng tham số ràng buộc | ĐẠT |
| 4 | `BaseRepository.php:49` | D | `query('SET time_zone = ?', [date('P')])`, giá trị do máy chủ sinh, vẫn qua tham số | ĐẠT |
| 5 | `NguoiDungRepository.php:158` | D | `query('SELECT ... WHERE id = ? FOR UPDATE', [$id])` | ĐẠT |
| 6 | `ThanhVienNhomRepository.php:35` | D | `query('INSERT ... VALUES (?, ?, ?) ...', [$deTaiId, $sinhVienId, $vaiTroNhom])` | ĐẠT |
| 7 | `ThongKeRepository.php:37, 80, 106` | D, I | Ba báo cáo tổng hợp (K7): `query($sql, ['tu' => ..., 'den' => ..., 'gv' => ...])`, câu SQL là chuỗi hằng, giá trị qua tham số có tên `:tu:`, `:den:`, `:gv:`. `tu_ngay`, `den_ngay` được `ThongKeService` kiểm đúng dạng `Y-m-d` (sai trả 422); `gv` lấy từ phiên đăng nhập, không lấy từ URL; loại báo cáo chỉ nhận 3 giá trị trong `ThongKeService::BAO_CAO` (khác trả 404) | ĐẠT |
| 8 | `DeTaiRepository.php:72` | I | `orderBy($sapXep, $huong)`: cột và hướng sắp xếp lấy từ tham số URL `sap_xep`, `huong`. Có danh sách trắng ở `DeTaiService.php:132–145`: `sap_xep` chỉ nhận `created_at`, `ten_de_tai`, `trang_thai`; `huong` chỉ nhận `ASC`, `DESC`; giá trị khác trả 422 trước khi vào Repository | ĐẠT (whitelist) |
| 9 | Các `orderBy(...)` còn lại trong `app/Repositories` | quét 2 | Chỉ nhận hằng số ('created_at', 'id', 'han_nop', …); `BaseRepository.php:66` nhận `$this->primaryKey` là thuộc tính khai báo cố định trong lớp | ĐẠT |
| 10 | `NguoiDungRepository.php:146`, `MocThoiGianRepository.php:28` | I | Tắt escape (`false`) nhưng toàn chuỗi hằng số | ĐẠT |
| 11 | `NguoiDungRepository.php:136–138` | I | `like(..., $tuKhoa)` (tham số `q`), Query Builder tự escape giá trị và ký tự `%`, `_` | ĐẠT |
| 12 | `DeTaiRepository.php:35–36` | I | Tìm kiếm đề tài (K5): `like('ten_de_tai', $tuKhoa)`, `orLike('mo_ta_pham_vi', $tuKhoa)` (tham số `tu_khoa`) | ĐẠT |
| 13 | `NhatKyHeThongRepository.php:92–96` | I | Tra cứu nhật ký (K8): `like()`/`orLike()` theo `tu_khoa` trên 5 cột | ĐẠT |
| 14 | `NhatKyHeThongRepository.php:84, 87, 100, 103` | quét 2 | `where('nk.nguoi_dung_id', ...)`, `where('nk.hanh_dong', ...)`, `where('nk.created_at >=', ...)`, `where('nk.created_at <=', ...)`: giá trị qua Query Builder; `nguoi_dung_id` ép `(int)`, `tu_ngay`/`den_ngay` kiểm dạng `YYYY-MM-DD` ở `NhatKyHeThongService.php:42–48` | ĐẠT |
| 15 | `NguoiDungRepository.php:129–132` | quét 2 | `whereIn('nd.id', subquery ... where('vt.ten_vai_tro', $tenVaiTro))` dùng Query Builder | ĐẠT |
| 16 | `DeTaiRepository.php:61, 141` | quét 2 | `whereIn('trang_thai', $chiTrangThai)`: mảng hằng do Service truyền | ĐẠT |
| 17 | Controllers: `getGet('page'/'size'/'trang_thai'/'vai_tro'/'q'/'chua_doc'/'lop_hoc_phan_id'/'tu_khoa'/'sap_xep'/'huong'/'nguoi_dung_id'/'hanh_dong'/'tu_ngay'/'den_ngay'/'dinh_dang')`, `getPost`, `getJSON` | quét 2 | `page`/`size`/`lop_hoc_phan_id`/`nguoi_dung_id` ép `(int)`; `trang_thai` ở `/de-tai` và `vai_tro` kiểm theo danh sách giá trị hợp lệ (sai trả 422); `trang_thai` ở `/nghiem-thu/ho-so`, `q`, `tu_khoa`, `hanh_dong` đi vào `where()`/`like()` của Query Builder; `sap_xep`, `huong` qua whitelist (dòng 8); `tu_ngay`, `den_ngay` kiểm dạng ngày; `dinh_dang` chỉ nhận `csv`, không đi vào SQL; `chua_doc` qua `filter_var` | ĐẠT |

Ghi chú không thuộc SQL injection: giá trị `size` cần có giới hạn trên để tránh `?size=1000000` kéo cả bảng. Trạng thái: ☑ đã có sẵn ☐ đã bổ sung ☐ còn tồn đọng. Cụ thể: `BaseController.php:105` hạ `size` về tối đa 100 (dùng cho `/nguoi-dung`, `/lop-hoc-phan`, …); `/de-tai` và `/nhat-ky-he-thong` trả 422 khi `size > 100`; `/nghiem-thu/ho-so` hạ về 100.

## 4. Kiểm thử thủ công (chụp ảnh kết quả)
Chạy chung với Ảnh 16 của V4 trên trang web đã phát hành.

| Chuỗi thử | Nơi thử | Kết quả mong đợi | Kết quả thực tế |
|---|---|---|---|
| `' OR '1'='1` | ô tìm kiếm người dùng (`q`), ô tìm kiếm đề tài (`tu_khoa`) | tìm theo đúng chuỗi, không trả toàn bộ | |
| `'; DROP TABLE de_tai;--` | ô tìm kiếm (`q`, `tu_khoa`) | không tác dụng | |
| `admin' --` | tên đăng nhập khi đăng nhập | đăng nhập thất bại | |
| `1 OR 1=1` | tham số `id` trên URL | không lộ bản ghi khác | |
| `x' OR 1=1 --` | `trang_thai`, `vai_tro` trên URL | 422 hoặc danh sách rỗng, không lỗi SQL | |
| `id; DROP TABLE de_tai` | `sap_xep` trên `/de-tai` | 422, không lỗi SQL | |
| `DESC; --` | `huong` trên `/de-tai` | 422, không lỗi SQL | |
| `2026-01-01' OR '1'='1` | `tu_ngay` trên `/nhat-ky-he-thong`, `/thong-ke/...` | 422 | |
| `?size=1000000` | các màn danh sách | bị chặn về mức tối đa (100) hoặc 422 | |

## 5. Kết luận
- Quét toàn bộ `app/` tại commit `acbd7e8`: không còn chỗ nào ghép chuỗi từ dữ liệu đầu vào vào câu SQL. Mọi truy vấn dùng tham số ràng buộc hoặc Query Builder; chuỗi `REGEXP` duy nhất ghép biến chỉ chứa hằng số và số nguyên.
- Chỗ duy nhất nhận tên cột từ đầu vào là sắp xếp danh sách đề tài (`DeTaiRepository.php:72`), đã có danh sách trắng ở `DeTaiService`.
- Giới hạn trên của `size`: đã có sẵn (xem mục 3).
- Kết luận chung: ☑ Không còn chỗ nào ghép chuỗi từ dữ liệu đầu vào

Ký xác nhận (V2): Nguyễn Quốc Khánh
