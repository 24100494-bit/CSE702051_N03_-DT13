# V3 – Danh mục điểm cuối đã tối ưu (Buổi 09)

## 1. Phạm vi thực hiện

Dự án: Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu (CodeIgniter 4, PHP/MySQL).

Vai trò: V3 – phụ trách nghiệp vụ.

Mục tiêu: giảm truy vấn lặp ở luồng nghiệm thu, áp dụng bộ nhớ đệm ngắn hạn cho điểm cuối danh sách hồ sơ nghiệm thu, đồng thời giữ nguyên kiểm tra quyền và dữ liệu riêng theo thành viên hội đồng.

## 2. Danh mục điểm cuối đã tối ưu

| Điểm cuối | Vấn đề trước tối ưu | Biện pháp | Tệp chính |
|---|---|---|---|
| `GET /api/v1/nghiem-thu/ho-so?page=&size=&trang_thai=` | Với N hồ sơ, Service gọi `thongKe()` và `daCham()` cho từng đề tài; số truy vấn tăng theo N. | Gom thống kê điểm theo danh sách ID bằng `GROUP BY de_tai_id`; truy vấn một lần danh sách đề tài mà thành viên hội đồng đã chấm. Cache 10 giây, khóa cache gồm user ID, trang, kích thước, trạng thái và phiên bản cache. | `app/Services/NghiemThuService.php`; `app/Repositories/DiemNghiemThuRepository.php` |
| `GET /api/v1/de-tai/{id}/ho-so-nghiem-thu` | Service truy vấn bản báo cáo mới nhất riêng cho từng mốc thời gian. Với M mốc, phần này tạo M truy vấn. | Truy vấn một lần với `NOT EXISTS` để chỉ lấy bản ghi mới nhất của từng mốc; thêm chỉ mục kết hợp `(de_tai_id, moc_thoi_gian_id, created_at, id)` để hỗ trợ truy vấn. | `app/Services/NghiemThuService.php`; `app/Repositories/BaoCaoMocRepository.php`; `schema.sql` |

## 3. Ước lượng số truy vấn theo cấu trúc mã nguồn

Đây là số truy vấn suy ra từ luồng gọi hàm, không phải số đo thời gian chạy trên máy thật.

### 3.1 Danh sách hồ sơ nghiệm thu

- Trước: 2 truy vấn để đếm và lấy trang, cộng 2 truy vấn cho mỗi hồ sơ: xấp xỉ `2 + 2N` truy vấn.
- Sau khi cache miss: 2 truy vấn để đếm và lấy trang, 1 truy vấn thống kê theo lô và 1 truy vấn danh sách đã chấm: tối đa 4 truy vấn khi trang có hồ sơ.
- Khi cache hit trong TTL 10 giây: không chạy lại các truy vấn nghiệp vụ của danh sách.

### 3.2 Chi tiết hồ sơ nghiệm thu

- Trước: phần tìm báo cáo mới nhất chạy một truy vấn cho mỗi mốc (M truy vấn).
- Sau: phần tìm báo cáo mới nhất chạy một truy vấn cho tất cả mốc (1 truy vấn); các truy vấn còn lại phục vụ đề tài, GVHD, thành viên, điểm và quyền xem vẫn được giữ nguyên.

## 4. An toàn cache và tính đúng đắn

- Cache danh sách được phân tách theo ID người dùng và bộ lọc; không dùng chung trường `toi_da_cham` giữa các thành viên hội đồng.
- Kiểm tra quyền vẫn nằm trong Filter/Service hiện có; cache không thay thế xác thực hoặc phân quyền.
- TTL 10 giây giới hạn thời gian dữ liệu danh sách có thể cũ. Sau khi chấm điểm hoặc chốt nghiệm thu thành công, Service tăng phiên bản cache để các khóa cũ không còn được sử dụng.
- Các luồng thay đổi trạng thái khác có thể khiến danh sách cache cũ tối đa 10 giây; khi đo nghiệm thu, cần chờ TTL hết hoặc gọi lại sau khi cache hết hạn.
- Không cache chi tiết hồ sơ chứa thông tin người dùng, tệp báo cáo và điểm.

Nếu cơ sở dữ liệu đã được tạo từ trước, chạy `indexes_buoi09.sql` một lần trong đúng database để bổ sung chỉ mục; script kiểm tra tên chỉ mục trước nên có thể chạy lại an toàn. Cơ sở dữ liệu tạo mới từ `schema.sql` đã có chỉ mục này.

## 5. Kiểm thử

Đã bổ sung kiểm thử đơn vị `tests/unit/NghiemThuServicePerformanceTest.php` để kiểm chứng Service dùng phương thức truy vấn theo lô và kết quả `so_luot_cham`, `diem_trung_binh`, `toi_da_cham` vẫn đúng.

Lệnh Git Bash tại thư mục gốc dự án:

```bash
./vendor/bin/phpunit --bootstrap "$(pwd)/vendor/codeigniter4/framework/system/Test/bootstrap.php" tests/unit/NghiemThuServicePerformanceTest.php
./vendor/bin/phpunit --bootstrap "$(pwd)/vendor/codeigniter4/framework/system/Test/bootstrap.php" tests/unit
```

## 6. Đo hiệu năng trước – sau

Chưa ghi số mili giây vào tài liệu này vì ZIP mã nguồn không chứa môi trường CSDL đang chạy và không cung cấp kết quả đo thực tế. Cần đo trên cùng máy, cùng dữ liệu, cùng tài khoản hội đồng và cùng bộ lọc.

| Điểm cuối | Số bản ghi bảng chính | Số mẫu | Trung vị trước (ms) | Trung vị sau (ms) | Ghi chú |
|---|---:|---:|---:|---:|---|
| `GET /api/v1/nghiem-thu/ho-so?page=1&size=20` | Ít nhất 5.000 đề tài hoặc dữ liệu thử tương đương | 50 mỗi lượt | Chưa đo | Chưa đo | Đo riêng lượt cache miss và cache hit |
| `GET /api/v1/de-tai/{id}/ho-so-nghiem-thu` | Ít nhất 5.000 bản ghi bảng chính theo yêu cầu chung của buổi | 50 mỗi lượt | Chưa đo | Chưa đo | Dùng đề tài có nhiều mốc thời gian |

Có thể dùng PowerShell 7 theo Sổ tay lệnh. Trên Windows PowerShell hiện có, nếu `Invoke-WebRequest` không hỗ trợ `-SkipHttpErrorCheck`, dùng `curl.exe` và lưu thời gian `time_total` theo hướng dẫn mục 8 của Sổ tay lệnh. Chỉ điền kết quả đo sau khi chạy thực tế.

## 7. Ảnh minh chứng cần tự chụp

1. Kết quả kiểm thử đơn vị thành công.
2. Cấu hình dữ liệu thử và số lượng bản ghi tối thiểu 5.000.
3. Bảng 50 mẫu đo trước và 50 mẫu đo sau, kèm trung vị.
4. Git diff thể hiện phần tối ưu và lịch sử commit của cá nhân.
