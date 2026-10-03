# V3 Buổi 06 – T3 Máy trạng thái và luồng nghiệp vụ

## 1. Khung thuật toán được chọn

Đề tài **Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu** phù hợp với khung **T3 – Máy trạng thái và luồng phê duyệt**, vì nghiệp vụ có các trạng thái `pending`, `approved`, `rejected` và luồng duyệt/từ chối/nộp lại.

Trong source hiện tại, hai luồng nghiệp vụ đã có:

- UC01/UC02: sinh viên đề xuất đề tài → thư ký phân công GVHD → GVHD duyệt hoặc từ chối.
- UC03/UC04: nhóm nộp báo cáo theo mốc → GVHD duyệt hoặc yêu cầu bổ sung → nhóm nộp lại.

Phần V3 Buổi 06 tập trung chuẩn hóa máy trạng thái và bổ sung API xem lịch sử trạng thái để chứng minh các chuyển trạng thái đã được ghi nhận.

## 2. Máy trạng thái đề tài

| Trạng thái hiện tại | Hành động | Trạng thái mới |
|---|---|---|
| `pending` | `duyet` | `approved` |
| `pending` | `tu_choi` | `rejected` |
| `pending` | `sua` | `pending` |
| `rejected` | `sua` | `pending` |
| `approved` | bất kỳ hành động trên | Không cho phép |
| `in_progress` | bất kỳ hành động trên | Không cho phép |
| `completed` | bất kỳ hành động trên | Không cho phép |

Hành động không có trong bảng chuyển trạng thái trả `422 INVALID_TRANSITION`.

## 3. Máy trạng thái báo cáo mốc

| Trạng thái hiện tại | Hành động | Trạng thái mới |
|---|---|---|
| `pending` | `duyet` | `approved` |
| `pending` | `yeu_cau_bo_sung` | `revision_requested` |
| `revision_requested` | `nop_lai` | `pending` |
| `approved` | bất kỳ hành động trên | Không cho phép |

## 4. Mã giả

```text
HAM CHUYEN_TRANG_THAI(trangThaiHienTai, hanhDong, bangChuyen):
    cacChuyen <- bangChuyen[trangThaiHienTai]

    NEU hanhDong khong nam trong cacChuyen:
        NEM loi 422 INVALID_TRANSITION

    TRA VE cacChuyen[hanhDong]
```

Luồng đề tài:

```text
Sinh vien de xuat
    -> pending

Neu GVHD duyet:
    pending -> approved

Neu GVHD tu choi:
    pending -> rejected
    rejected -> pending khi sinh vien sua va gui lai

Moi chuyen trang thai deu duoc ghi audit log.
```

Luồng báo cáo mốc:

```text
Nhom truong nop bao cao
    -> pending

GVHD duyet
    -> approved

GVHD yeu cau bo sung
    -> revision_requested

Nhom truong nop lai
    -> pending
```

## 5. Kiểm chứng

Các ca kiểm thử đơn vị trong `tests/unit/TrangThaiWorkflowTest.php` kiểm tra:

1. `pending -> approved` khi duyệt đề tài.
2. `pending -> rejected -> pending` khi từ chối rồi sửa lại.
3. Không cho phép duyệt lại đề tài đã `approved`.
4. `pending -> revision_requested -> pending` với báo cáo mốc.
5. Không cho phép `nop_lai` khi báo cáo chưa ở `revision_requested`.

Ngoài ra, hai API lịch sử trạng thái được bổ sung:

- `GET /api/v1/de-tai/{id}/lich-su`
- `GET /api/v1/bao-cao-moc/{id}/lich-su`

Cả hai dùng quyền chức năng `F3.10` và tiếp tục kiểm tra quyền trên đúng đối tượng ở Service để tránh đọc lịch sử của đối tượng không thuộc phạm vi người dùng.
