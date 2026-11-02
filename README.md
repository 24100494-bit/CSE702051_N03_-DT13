# 🎓 Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu

Hệ thống quản lý đồ án môn học, đồ án tốt nghiệp và đề tài nghiên cứu trong một khoa. Dự án được xây dựng bằng **PHP** với **CodeIgniter 4.6** theo kiến trúc phân tầng **Controller → Service → Repository**, giao diện HTML, CSS và JavaScript gọi API, dữ liệu lưu trên **MariaDB**.

🌐 **Dùng thử trực tuyến:** https://htquanlydavadtnc.freepage.cc

## 📖 Giới thiệu

Hệ thống phục vụ trọn luồng công việc của một đề tài, gồm năm vai trò:

- **Khách:** xem và tìm đề tài trên trang công khai, đăng ký, đăng nhập, yêu cầu đặt lại mật khẩu
- **Sinh viên:** đề xuất đề tài, quản lý nhóm, nộp báo cáo theo mốc, xem nhận xét, điểm và lịch sử đề tài
- **Giảng viên hướng dẫn:** duyệt hoặc từ chối đề tài, duyệt báo cáo hoặc yêu cầu bổ sung, xem thống kê trong phạm vi phụ trách
- **Hội đồng nghiệm thu:** xem hồ sơ đủ điều kiện, chấm điểm từ 0 đến 10
- **Thư ký khoa:** quản lý tài khoản, lớp học phần và mốc thời gian, phân công giảng viên, chốt nghiệm thu, xem nhật ký, thống kê và xuất CSV

## 🔄 Luồng trạng thái đề tài

```
pending ──duyệt──▶ approved ──nộp bản đầu──▶ in_progress ──đủ mốc bắt buộc──▶ completed ──chốt──▶ accepted
   │  ▲
từ chối  sửa lại
   ▼  │
 rejected
```

Đề tài chỉ chuyển sang `completed` khi mọi mốc bắt buộc của lớp đều có bản nộp được duyệt. Mỗi lần nộp lại báo cáo tạo một bản ghi mới nên giữ được toàn bộ lịch sử.

## 🏗️ Cấu trúc dự án

```
CSE702051_N03_-DT13/
├── app/
│   ├── Config/                       # Cấu hình, định tuyến (Routes.php), bảng quyền (PhanQuyen.php)
│   ├── Controllers/                  # Nhận yêu cầu, trả JSON (DeTai, BaoCaoMoc, NghiemThu, XacThuc...)
│   ├── Services/                     # Nghiệp vụ, máy trạng thái, giao dịch, kiểm quyền trên đối tượng
│   ├── Repositories/                 # Truy xuất dữ liệu bằng truy vấn tham số hóa
│   ├── Filters/                      # Phân quyền, kiểm nguồn (CSRF), tiêu đề bảo mật, khóa nội bộ
│   ├── Exceptions/                   # Lỗi nghiệp vụ (không đủ quyền, không tìm thấy, dữ liệu sai...)
│   └── Views/                        # Trang công khai, đăng nhập, trang chủ, trang lỗi
├── public/
│   ├── assets/                       # CSS, JavaScript, phông chữ
│   ├── index.php
│   ├── robots.txt
│   └── sitemap.xml
├── tests/
│   ├── unit/                         # Kiểm thử đơn vị (PHPUnit)
│   └── api/                          # Bộ 25 ca kiểm thử API (Postman, newman)
├── docs/                             # Tài liệu theo buổi: mã giả, rà soát, đo hiệu năng, WCAG
├── schema.sql                        # Lược đồ cơ sở dữ liệu (13 bảng)
├── seed.sql, seed_extended.sql       # Dữ liệu mẫu
├── trigger.sql                       # 5 trigger
├── Tep dac ta API.yaml               # Đặc tả OpenAPI
├── Ma tran phan quyen.xlsx           # Ma trận phân quyền và các ca kiểm thử
├── .env.example                      # Mẫu tệp cấu hình
└── README.md
```

## 🛠️ Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Máy chủ | PHP 8.x, CodeIgniter 4.6, Composer |
| Giao diện | HTML, CSS, JavaScript thuần |
| Cơ sở dữ liệu | MariaDB 11.4 trên hosting, MariaDB 10.4 khi chạy trên máy |
| Triển khai | InfinityFree, HTTPS, tải mã nguồn bằng FTP |
| Kiểm thử | PHPUnit, Postman, newman, Lighthouse |

## ✨ Tính năng chính

- 🔐 **Xác thực:** đăng ký, đăng nhập, đổi và đặt lại mật khẩu qua `XacThucController`
- 📝 **Đề tài:** đề xuất, sửa, xóa, duyệt, từ chối, tìm kiếm và xem lịch sử qua `DeTaiController`
- 👥 **Nhóm sinh viên:** thêm, xóa thành viên và phân công giảng viên qua `NhomSinhVienController`
- 📅 **Lớp và mốc:** quản lý lớp học phần, mốc nộp qua `LopHocPhanController`, `MocThoiGianController`
- 📤 **Báo cáo theo mốc:** nộp, duyệt, yêu cầu bổ sung, tải tệp qua `BaoCaoMocController`, `TaiTepController`
- 🏅 **Nghiệm thu:** xem hồ sơ, chấm điểm, chốt kết quả qua `NghiemThuController`
- 📊 **Thống kê:** theo lớp, tiến độ nộp, theo giảng viên, xuất CSV qua `ThongKeController`
- 🔔 **Thông báo:** thông báo trong hệ thống và nhắc mốc sắp hết hạn qua `ThongBaoController`
- 📜 **Nhật ký:** tra cứu thao tác trong hệ thống qua `NhatKyHeThongController`
- 🌍 **Trang công khai:** khách xem và tìm đề tài qua `CongKhaiController`

## 🛡️ Bảo mật

- Mật khẩu băm bằng bcrypt (cost 12); sai 5 lần trong 15 phút, tính theo địa chỉ IP hoặc tài khoản, thì tạm chặn và trả mã 429
- Tạo lại mã phiên sau đăng nhập; cookie phiên có `HttpOnly`, `Secure`, `SameSite=Lax`
- Phân quyền hai mức: bộ lọc kiểm vai trò, tầng Service kiểm người gọi có đúng quan hệ với đề tài hoặc báo cáo đó không
- Chống CSRF bằng kiểm tra nguồn, chống SQL Injection bằng truy vấn tham số hóa, chống XSS bằng mã hóa nội dung và CSP
- Tệp nộp chỉ nhận `.pdf`, `.docx` tối đa 5 MB, kiểm tra chữ ký thật của tệp, lưu tên ngẫu nhiên ngoài thư mục web

## 🚀 Cài đặt và chạy dự án

Hướng dẫn dành cho Windows, các lệnh chạy trong **Command Prompt (cmd)** vì PowerShell không nhận dấu `<`.

### Yêu cầu

- [XAMPP](https://www.apachefriends.org) với PHP 8.1 trở lên
- [Composer](https://getcomposer.org/download) (khi cài, chọn tệp `C:\xampp\php\php.exe`)
- [Git](https://git-scm.com), không bắt buộc nếu tải bằng ZIP

Mở `C:\xampp\php\php.ini`, xóa dấu `;` ở đầu hai dòng sau rồi lưu lại:

```ini
extension=intl
extension=zip
```

### 1. Tải dự án

```bat
git clone -b develop https://github.com/24100494-bit/CSE702051_N03_-DT13.git
cd CSE702051_N03_-DT13
```

Hoặc trên GitHub chọn nhánh `develop`, bấm **Code** rồi **Download ZIP**, giải nén và dùng `cd` để vào thư mục đó.

### 2. Tạo cơ sở dữ liệu

Mở **XAMPP Control Panel**, bấm **Start** ở dòng **MySQL**, rồi chạy lần lượt:

```bat
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE quan_ly_do_an CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 quan_ly_do_an < schema.sql
C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 quan_ly_do_an < seed.sql
C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 quan_ly_do_an < seed_extended.sql
C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 quan_ly_do_an < trigger.sql
```

> Các tệp `seed_bulk.sql`, `seed_bulk_buoi9.sql`, `seed_buoi6.sql` là dữ liệu đo hiệu năng và kiểm thử, không cần nạp khi cài đặt thông thường.

### 3. Tạo tệp cấu hình

```bat
copy .env.example .env
notepad .env
```

Sửa các dòng sau rồi lưu lại:

```env
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'
database.default.hostname = 127.0.0.1
database.default.database = quan_ly_do_an
database.default.username = root
database.default.password =
cookie.secure = false
```

### 4. Cài thư viện và chạy

```bat
composer install
php spark serve
```

Mở http://localhost:8080 để dùng. Nếu http://localhost:8080/api/v1/health báo `"database": "ok"` là đã cài xong.

## 👤 Tài khoản thử

| Vai trò | Tên đăng nhập |
|---|---|
| Thư ký khoa | `thukykhoa` |
| Giảng viên hướng dẫn | `gv_nguyen_van_a` |
| Sinh viên | `sv_tran_van_b`, `sv_le_thi_c` |
| Hội đồng nghiệm thu | `hd_pham_van_d` |

Mật khẩu nằm trong bảng tài khoản kiểm thử của gói nộp. Dữ liệu mẫu đều là dữ liệu mô phỏng phục vụ học tập.

## 🧪 Kiểm thử

Kiểm thử đơn vị:

```bat
vendor\bin\phpunit --bootstrap vendor\codeigniter4\framework\system\Test\bootstrap.php tests\unit
```

Bộ 25 ca API chạy bằng newman, xem [tests/api/README.md](tests/api/README.md).

## ⚙️ Cấu hình

Mọi giá trị phụ thuộc môi trường nằm trong `.env`, không đưa lên repository. Nếu dùng Apache, trỏ thư mục gốc web vào `public/`. Hosting không đổi được thư mục gốc thì `.htaccess` ở gốc dự án sẽ chuyển yêu cầu vào `public/`.

---

**Thực hiện:** Nhóm 13, lớp CSE702051_N03, đề tài DT13.
