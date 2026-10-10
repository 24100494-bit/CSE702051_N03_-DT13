# Buổi 08 – V3: Danh mục điểm cuối đã rà soát kiểm quyền trên đối tượng

**Dự án:** Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu – Nhóm 03, DT13  
**Vai trò:** V3 – Phụ trách nghiệp vụ  
**Căn cứ:** Ma trận phân quyền.xlsx trong repository; các route trong `app/Config/Routes.php`; kiểm tra quyền nghiệp vụ tại Service.

## 1. Phạm vi công việc

V3 rà soát quyền ở mức đối tượng (object-level authorization), không chỉ dựa vào việc người dùng có đăng nhập hoặc có vai trò chức năng. Với đối tượng có định danh trong URL, Service phải tải bản ghi liên quan và xác nhận người dùng có quan hệ phù hợp với đúng bản ghi đó trước khi đọc hoặc thay đổi dữ liệu.

Đợt rà soát này phát hiện một điểm không khớp Ma trận phân quyền: một số luồng đọc đang cho phép **mọi thành viên nhóm** xem chi tiết đề tài, báo cáo mốc và điểm nghiệm thu, trong khi ma trận ghi rõ quyền của sinh viên ở các chức năng F2.3, F3.6, F3.10 và F4.4 là **Chủ đề tài**. Đã thu hẹp các kiểm tra tương ứng về đúng chủ đề tài. Quyền xem theo nhóm vẫn được giữ ở các chức năng mà ma trận cho phép theo nhóm, ví dụ F2.10 và F3.11.

## 2. Danh mục điểm cuối đã rà soát

| Mã | Điểm cuối | Quy tắc kiểm quyền trên đối tượng | Vị trí kiểm tra / kết quả rà soát |
|---|---|---|---|
| F1.4 | `PATCH /api/v1/nguoi-dung/toi/mat-khau` | Chỉ đổi mật khẩu của tài khoản đang đăng nhập | Route `quyen:F1.4`; Service nhận ID từ người dùng phiên hiện tại, không lấy ID mục tiêu từ request |
| F1.5 | `GET/PATCH /api/v1/nguoi-dung/toi` | Chỉ xem/cập nhật hồ sơ của chính mình | Route `quyen:F1.5`; Controller dùng người dùng hiện tại |
| F2.3 | `GET /api/v1/de-tai/{id}` | Chủ đề tài; GVHD phụ trách; Thư ký khoa; Hội đồng khi đề tài đủ điều kiện nghiệm thu/đã nghiệm thu | `DeTaiService::getForUser`; đã thu hẹp quyền sinh viên về chủ đề tài |
| F2.4 | `PATCH /api/v1/de-tai/{id}` | Chủ đề tài; chỉ trạng thái được phép sửa | `DeTaiService::capNhat` → `layDeTaiCuaChu` và máy trạng thái |
| F2.5 | `DELETE /api/v1/de-tai/{id}` | Chủ đề tài; chỉ đề xuất chưa được duyệt hoặc bị từ chối | `DeTaiService::xoa` → `layDeTaiCuaChu` và máy trạng thái |
| F2.6–F2.7 | `POST /api/v1/de-tai/{id}/duyet`, `/tu-choi` | Chỉ GVHD được phân công cho đúng đề tài | `DeTaiService::chuyenTrangThaiBoiGvhd` kiểm tra `gvhd_id` của bản ghi |
| F2.9 | `GET /api/v1/de-tai?...` | Danh sách được lọc theo phạm vi người dùng: đề tài của nhóm, đề tài GVHD phụ trách, Hội đồng chỉ thấy trạng thái nghiệm thu | `DeTaiService::listForUser` và tham số lọc của Repository |
| F2.10 | `GET /api/v1/nhom-sinh-vien/{id}` | Thành viên thuộc nhóm đề tài hoặc Thư ký khoa | `NhomSinhVienService::xem` |
| F2.11 | `POST/DELETE /api/v1/nhom-sinh-vien/{id}/thanh-vien...` | Nhóm trưởng của đúng đề tài hoặc Thư ký khoa; kiểm tra thành viên đích và trạng thái đề tài | `NhomSinhVienService` |
| F3.4 | `POST /api/v1/de-tai/{deTaiId}/bao-cao-moc` | Chủ đề tài mới được nộp báo cáo cho đề tài của mình | `BaoCaoMocService::layDeTaiCuaNhomTruong` |
| F3.5 | `POST /api/v1/bao-cao-moc/{id}` | Chủ đề tài của đề tài liên quan; bản nộp mới nhất phải bị yêu cầu bổ sung | `BaoCaoMocService::nopLai` và `layDeTaiCuaNhomTruong` |
| F3.6 | `GET /api/v1/bao-cao-moc/{id}` và `GET /api/v1/de-tai/{deTaiId}/bao-cao-moc` | Chủ đề tài, GVHD phụ trách hoặc Thư ký khoa | `BaoCaoMocService::kiemQuyenXem`; đã thu hẹp quyền sinh viên về chủ đề tài |
| F3.7–F3.8 | `POST /api/v1/bao-cao-moc/{id}/duyet`, `/yeu-cau-bo-sung` | Chỉ GVHD phụ trách đúng đề tài chứa báo cáo | `BaoCaoMocService::xuLyBoiGvhd` kiểm tra `gvhd_id` trong giao dịch |
| F3.10 | `GET /api/v1/de-tai/{id}/lich-su`, `GET /api/v1/bao-cao-moc/{id}/lich-su` | Chủ đề tài hoặc GVHD phụ trách đề tài liên quan | `DeTaiService::lichSu` và `BaoCaoMocService::lichSu`; quyền sinh viên theo đúng F2.3/F3.6 |
| F3.11 | `GET /api/v1/bao-cao-moc/{id}/tep` | Thư ký khoa; GVHD phụ trách; thành viên nhóm; Hội đồng khi đề tài đủ điều kiện/đã nghiệm thu | `TaiTepService::baoCaoMoc` kiểm tra quyền trên đề tài trước khi mở tệp và kiểm tra đường dẫn tệp |
| F4.2 | `GET /api/v1/de-tai/{id}/ho-so-nghiem-thu` | Hội đồng và đề tài ở trạng thái đủ điều kiện/đã nghiệm thu | `NghiemThuService::layDeTaiChoHoiDong` |
| F4.3 | `POST /api/v1/de-tai/{id}/diem-nghiem-thu` | Hội đồng; đề tài đang chờ chấm; mỗi thành viên chỉ chấm một lần | `NghiemThuService::cham` |
| F4.4 | `GET /api/v1/de-tai/{id}/diem-nghiem-thu` | Thư ký khoa; Hội đồng trong trạng thái hợp lệ; chủ đề tài và GVHD phụ trách chỉ xem sau khi chốt | `NghiemThuService::xemDiem`; đã thu hẹp quyền sinh viên về chủ đề tài |
| F5.1 | `GET /api/v1/thong-ke/{loai}` | GVHD chỉ xem phạm vi phụ trách; Thư ký khoa xem toàn khoa | `ThongKeService::xem` và Repository giới hạn dữ liệu theo GVHD |
| F6.1–F6.2 | `GET /api/v1/thong-bao`, `PATCH /api/v1/thong-bao/{id}/da-doc` | Chỉ xem/đánh dấu thông báo của chính mình | `ThongBaoService::cuaToi` và `danhDauDaDoc`; ID chủ sở hữu được so với người dùng hiện tại |

Các điểm cuối chỉ giới hạn theo vai trò/chức năng (ví dụ quản lý lớp học phần, mốc thời gian, người dùng, nhật ký hệ thống và xuất báo cáo) vẫn có bộ lọc vai trò ở Routes/Service. Chúng không được xem là kiểm quyền theo đối tượng cá nhân nếu ma trận cho phép vai trò đó thao tác trên toàn bộ tập dữ liệu tương ứng.

## 3. Các thay đổi trong đợt rà soát

1. `app/Services/DeTaiService.php`: F2.3 và lịch sử F3.10 không còn cấp quyền xem chi tiết cho sinh viên chỉ vì là thành viên khác chủ đề tài.
2. `app/Services/BaoCaoMocService.php`: F3.6 và lịch sử F3.10 chỉ cho sinh viên là chủ đề tài xem báo cáo/lịch sử; quyền xem tệp theo nhóm F3.11 không thay đổi.
3. `app/Services/NghiemThuService.php`: F4.4 chỉ cho chủ đề tài xem điểm sau khi chốt; thành viên khác không được nhận dữ liệu điểm chỉ vì thuộc nhóm.
4. `tests/unit/DeTaiServiceTest.php`: thêm kiểm thử chủ đề tài được xem và thành viên khác bị từ chối.

## 4. Điểm cuối trong ma trận chưa thấy khai báo trong Routes.php

Đối chiếu `Ma tran phan quyen.xlsx` với `app/Config/Routes.php` của ZIP cho thấy chưa tìm thấy route tương ứng cho các điểm cuối sau:

- F2.1 – `GET /api/v1/public/de-tai` (danh sách đề tài công khai).
- F3.9 – `POST /api/v1/internal/moc-thoi-gian/quet-qua-han` (tác vụ nội bộ).
- F6.3 – `POST /api/v1/internal/thong-bao/nhac-han` (tác vụ nội bộ).

Các điểm cuối này không được tự ý tạo trong phần chỉnh sửa V3 vì cần thống nhất với người phụ trách nghiệp vụ/tích hợp và cơ chế gọi nội bộ. Cần ghi nhận như hạng mục còn thiếu của repository, sau đó giao đúng thành viên bổ sung route và cơ chế xác thực nội bộ trước khi kiểm thử. Không coi chúng là đã được bảo vệ chỉ vì đã xuất hiện trong ma trận.

## 5. Ca kiểm thử cần chạy và lưu bằng chứng

| Mã | Tài khoản / thao tác | Kết quả mong đợi |
|---|---|---|
| V3-PQ-01 | Chủ đề tài gọi `GET /api/v1/de-tai/{id}` của mình | `200` |
| V3-PQ-02 | Sinh viên là thành viên nhưng không phải chủ đề tài gọi `GET /api/v1/de-tai/{id}` | `403` |
| V3-PQ-03 | Sinh viên là thành viên nhưng không phải chủ đề tài gọi `GET /api/v1/bao-cao-moc/{id}` | `403` |
| V3-PQ-04 | Sinh viên là thành viên nhưng không phải chủ đề tài gọi `GET /api/v1/de-tai/{id}/diem-nghiem-thu` sau khi chốt | `403` |
| V3-PQ-05 | GVHD không phụ trách thử duyệt đề tài/báo cáo của GVHD khác | `403`, dữ liệu không thay đổi |
| V3-PQ-06 | GVHD phụ trách xem đề tài/báo cáo được phân công | `200` nếu bản ghi tồn tại và phù hợp nghiệp vụ |
| V3-PQ-07 | Thành viên nhóm khác thử tải tệp báo cáo | `403` |
| V3-PQ-08 | Thành viên hợp lệ của nhóm tải tệp theo F3.11 | Được phép nếu trạng thái và bản ghi hợp lệ |
| V3-PQ-09 | Người dùng A đánh dấu thông báo của người dùng B đã đọc | `404` hoặc kết quả từ chối theo xử lý hiện có; thông báo không bị đổi |
| V3-PQ-10 | Người chưa đăng nhập gọi điểm cuối có filter `quyen` | `401` |

> Chưa đánh dấu các ca trên là “đã chạy”. Hãy chạy trên môi trường dự án có cơ sở dữ liệu và tài khoản với vai trò phù hợp, ghi lại mã HTTP/kết quả thực tế và ảnh minh chứng. Không dùng dữ liệu hoặc ảnh minh chứng giả.

## 6. Kiểm tra tại máy

Từ thư mục gốc dự án:

```bash
php -l app/Services/DeTaiService.php
php -l app/Services/BaoCaoMocService.php
php -l app/Services/NghiemThuService.php
php -l tests/unit/DeTaiServiceTest.php
vendor/bin/phpunit tests/unit/DeTaiServiceTest.php
```

Nếu không có `vendor/bin/phpunit`, cài phụ thuộc theo cấu hình dự án bằng `composer install` trước khi chạy kiểm thử. Kiểm thử HTTP các ca ở Mục 4 cần môi trường và dữ liệu thử thực tế.
