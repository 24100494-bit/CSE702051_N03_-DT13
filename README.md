# Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu

Hệ thống hỗ trợ quản lý đồ án môn học, đồ án tốt nghiệp và đề tài nghiên cứu, tập trung vào **luồng công việc (workflow)** đề xuất – duyệt đề tài, nộp – duyệt báo cáo theo mốc, nghiệm thu, và **kiểm soát tiến độ**.

- **Nhóm:** CSE702051_N03 – Nhóm 13 – Đề tài DT13 (nhánh A: PHP/MySQL trên InfinityFree)
- **Địa chỉ trực tuyến:** https://htquanlydavadtnc.freepage.cc (đăng nhập tại `/dang-nhap`, API tại `/api/v1`)
- **Công nghệ:** PHP 8.x, CodeIgniter 4.6, MariaDB / MySQL, giao diện HTML + JavaScript gọi API

## Yêu cầu

- PHP `>= 8.1` với các extension `mysqli`, `intl`, `mbstring`, `curl`, `json`. Với XAMPP, bỏ dấu `;` ở dòng `extension=mysqli` và `extension=intl` trong `php.ini` nếu còn.
- MariaDB `>= 10.4` hoặc MySQL `>= 8.0.16` (cần ràng buộc CHECK). Các tệp chỉ mục dùng cú pháp `CREATE INDEX IF NOT EXISTS` của MariaDB.
- Composer 2.x. Node.js và newman chỉ cần khi chạy bộ kiểm thử API.

## Chạy bằng PHP / XAMPP

1. Tạo CSDL `quan_ly_do_an` (bảng mã `utf8mb4_unicode_ci`).
2. Nạp các tệp SQL theo **đúng thứ tự**:
   `schema.sql` → `seed.sql` → `seed_extended.sql` → `trigger.sql`.
   `schema.sql` tạo đủ 13 bảng, ràng buộc và chỉ mục; `trigger.sql` thêm 5 trigger (chỉ máy cục bộ, InfinityFree không cho tạo trigger).
   Các tệp `cap_nhat_csdl_buoi*.sql`, `indexes_*.sql` là bản cập nhật theo buổi đã gộp vào hai tệp trên, chỉ dùng khi nâng cấp CSDL cũ.
   Không nạp `seed_bulk.sql`, `seed_bulk_buoi9.sql` (dữ liệu khoảng 20.000 dòng chỉ để đo hiệu năng) và `seed_buoi6.sql` (dữ liệu kiểm thử đồng thời) khi cài đặt thông thường.
3. Chép `.env.example` thành `.env`. Tệp mẫu để trống địa chỉ web và thông tin CSDL, giữ cấu hình mặc định của môi trường trực tuyến (`production`, `cookie.secure = true`); trên máy cục bộ điền và sửa lại:
   ```env
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost:8080/'
   database.default.hostname = 127.0.0.1
   database.default.database = quan_ly_do_an
   database.default.username = root
   database.default.password =
   cookie.secure = false
   ```
4. Chạy `composer install`.
5. Chạy `php spark serve`, mở `http://localhost:8080`. Kiểm tra nhanh: `http://localhost:8080/api/v1/health`.
   Nếu dùng Apache thì trỏ DocumentRoot thẳng vào thư mục `public/` (trang gọi `/assets/...` và `/api/v1/...` tính từ gốc tên miền, chạy trong thư mục con sẽ hỏng giao diện).

Trên InfinityFree không đổi được thư mục gốc web, nên tệp `.htaccess` ở thư mục gốc chuyển mọi yêu cầu vào `public/` và chặn truy cập `.env`, `app/`, `vendor/`, `writable/`.

## Tài khoản kiểm thử

Bốn vai trò: Thư ký khoa `thukykhoa`, Giảng viên hướng dẫn `gv_nguyen_van_a`, Sinh viên `sv_tran_van_b`, `sv_le_thi_c`, Hội đồng nghiệm thu `hd_pham_van_d`. Mật khẩu xem bảng tài khoản kiểm thử trong `04_TAILIEU` của gói nộp.

## Kiểm thử

- Kiểm thử đơn vị (sau `composer install`):
  `vendor/bin/phpunit --bootstrap vendor/codeigniter4/framework/system/Test/bootstrap.php tests/unit`
  (Windows: `vendor\bin\phpunit`; nếu báo không mở được tệp bootstrap thì ghi đường dẫn đầy đủ).
- Kiểm thử API 25 ca bằng newman: xem `tests/api/README.md`.

## Cấu trúc thư mục

```
app/Config/        cấu hình, định tuyến (Routes.php), ma trận phân quyền (PhanQuyen.php)
app/Controllers/   tầng điều khiển: nhận yêu cầu, trả JSON
app/Services/      tầng nghiệp vụ: kiểm quyền trên đối tượng, máy trạng thái, giao dịch
app/Repositories/  tầng truy cập dữ liệu: truy vấn tham số hóa
app/Filters/       xác thực, phân quyền, kiểm nguồn (CSRF), tiêu đề bảo mật
app/Views/         trang giao diện
public/            index.php, tài nguyên tĩnh, robots.txt, sitemap.xml
tests/             kiểm thử đơn vị (unit) và API (api)
docs/              tài liệu theo buổi: mã giả, rà soát truy vấn, đo hiệu năng, đối chiếu WCAG
*.sql              lược đồ, dữ liệu mẫu, cập nhật CSDL, chỉ mục, truy vấn báo cáo
Tep dac ta API.yaml  đặc tả OpenAPI 3.0
```

## Lưu ý bảo mật

Không đưa `.env` thật, mật khẩu thật, phiên làm việc hay dữ liệu cá nhân vào repository hoặc gói nộp; chỉ dùng `.env.example` làm mẫu. Dữ liệu trong các tệp seed là dữ liệu mô phỏng phục vụ mục đích học tập.
