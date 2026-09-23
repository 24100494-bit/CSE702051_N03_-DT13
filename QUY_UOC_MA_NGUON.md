# Quy ước mã nguồn - Nhóm 03 (DT13)
## Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu

Tài liệu này áp dụng cho toàn bộ mã nguồn của dự án (Nhánh A - PHP). Mọi thành viên đọc kỹ trước khi bắt đầu code phần được phân công.

---

## 1. Danh sách thực thể dữ liệu chính (tối thiểu 06 theo Phụ lục 03)

| # | Thực thể | Mô tả ngắn |
|---|---|---|
| 1 | `nguoi_dung` | Tài khoản dùng chung cho mọi vai trò (SV, GVHD, Thư ký, Hội đồng) |
| 2 | `de_tai` | Thông tin đề tài, trạng thái workflow |
| 3 | `nhom_sinh_vien` | Nhóm sinh viên thực hiện đề tài |
| 4 | `thanh_vien_nhom` | Bảng trung gian nhóm - sinh viên |
| 5 | `moc_thoi_gian` | Các mốc chung của lớp học phần |
| 6 | `bao_cao_moc` | Báo cáo nộp theo từng mốc, gắn với đề tài |
| 7 | `nhat_ky_he_thong` | Log các hành vi nhạy cảm |
| 8 | `thong_bao` | Thông báo gửi tới người dùng |
| 9 | `diem_nghiem_thu` | Điểm và nhận xét của hội đồng |

> Người phụ trách dữ liệu (V2) dựa vào danh sách này để vẽ ERD chi tiết và bổ sung thuộc tính từng bảng.

---

## 2. Quy ước đặt tên cơ sở dữ liệu

- **Tên bảng**: số ít, không dấu, snake_case — ví dụ `de_tai`, `sinh_vien`, `bao_cao_moc`.
- **Khóa chính**: luôn đặt tên cột là `id` (kiểu `BIGINT UNSIGNED AUTO_INCREMENT`).
- **Khóa ngoại**: đặt tên dạng `<ten_bang>_id`, ví dụ `de_tai_id`, `nguoi_dung_id`.
- **Cột thời gian**: dùng thống nhất `created_at`, `updated_at` (và `deleted_at` nếu dùng soft-delete).
- **Cột trạng thái**: dùng kiểu `ENUM` hoặc `VARCHAR` có giá trị rõ ràng bằng tiếng Anh không dấu, ví dụ `pending`, `approved`, `rejected`, `overdue`.
- **Bảng trung gian (n-n)**: đặt tên ghép 2 bảng theo thứ tự alphabet, ví dụ `nhom_sinh_vien_thanh_vien`.

---

## 3. Quy ước đặt tên mã nguồn

| Đối tượng | Quy ước | Ví dụ |
|---|---|---|
| Class (Controller, Service, Model) | PascalCase | `DeTaiController`, `BaoCaoMocService` |
| Biến, hàm/phương thức | camelCase | `getDeTaiById()`, `$danhSachDeTai` |
| Tên file view/blade | kebab-case hoặc snake_case | `de-tai-danh-sach.blade.php` |
| Route API | số nhiều, kebab-case, có tiền tố version | `/api/v1/de-tai`, `/api/v1/bao-cao-moc` |
| Nhánh Git | `feature/<mo-ta-ngan>`, `bugfix/<mo-ta-ngan>` | `feature/duyet-de-tai` |
| Commit message | `[Module] Hành động: mô tả ngắn` | `[DeTai] Add: API duyệt đề tài` |

**Áp dụng đồng nhất cho cả 3 tầng**: Controller (điều khiển) → Service (nghiệp vụ) → Repository/Model (truy cập dữ liệu). Không viết truy vấn SQL trực tiếp trong Controller.

---

## 4. Cấu trúc phản hồi API thống nhất

**Thành công:**
```json
{
  "status": "success",
  "data": { },
  "message": "Thao tác thành công"
}
```

**Lỗi:**
```json
{
  "status": "error",
  "code": 422,
  "message": "Dữ liệu không hợp lệ",
  "errors": {
    "ten_de_tai": ["Không được để trống"]
  }
}
```

- Dùng đúng mã trạng thái HTTP theo ngữ nghĩa: `200` (thành công), `201` (tạo mới), `400`/`422` (dữ liệu sai), `401` (chưa xác thực), `403` (không đủ quyền), `404` (không tìm thấy), `500` (lỗi máy chủ).
- Không bao giờ để lộ thông báo lỗi chi tiết của hệ thống (stack trace) ra ngoài môi trường trực tuyến.

---

## 5. Quy tắc làm việc chung

- Code trên nhánh riêng theo tính năng (`feature/...`), tạo Pull Request vào `develop`, không đẩy thẳng vào `main`/`develop`.
- Mỗi Pull Request cần ít nhất 1 thành viên khác review trước khi merge.
- Dữ liệu mẫu phải là dữ liệu giả lập, không dùng thông tin thật của bất kỳ ai.

---
*Cập nhật lần cuối bởi: Lê Thị Thúy Hằng (Nhóm trưởng) - Buổi 3*
