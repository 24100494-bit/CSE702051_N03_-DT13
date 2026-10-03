# V3 Buổi 06 – T3 Máy trạng thái và luồng nghiệp vụ

## 1. Khung thuật toán được chọn

Đề tài **Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu** phù hợp với khung **T3 – Máy trạng thái và luồng phê duyệt**, vì đề tài đi qua chuỗi trạng thái `pending → approved → in_progress → completed → accepted` và có các luồng duyệt/từ chối/nộp lại.

Ba luồng nghiệp vụ dùng chung máy trạng thái:

- **Luồng 1** – UC01/UC02: sinh viên đề xuất đề tài → thư ký phân công GVHD → GVHD duyệt hoặc từ chối.
- **Luồng 2** – UC03/UC04: nhóm nộp báo cáo theo mốc → GVHD duyệt hoặc yêu cầu bổ sung → nhóm nộp lại.
- **Luồng 3** – UC06: đủ mọi mốc bắt buộc → hội đồng chấm điểm (0–10, mỗi thành viên một lần) → thư ký khoa chốt → đề tài đã nghiệm thu, điểm cuối là trung bình các lượt chấm.

Bảng chuyển trạng thái khai báo dạng dữ liệu (`DeTaiService::CHUYEN_TRANG_THAI`, `BaoCaoMocService::CHUYEN_TRANG_THAI`); lớp `TrangThaiWorkflow` chỉ tra bảng. Thêm bước mới là thêm một dòng vào bảng, không sửa thuật toán.

## 2. Máy trạng thái đề tài

| Trạng thái hiện tại | Hành động | Trạng thái mới | Ai / khi nào |
|---|---|---|---|
| `pending` | `duyet` | `approved` | GVHD phụ trách |
| `pending` | `tu_choi` | `rejected` | GVHD phụ trách, bắt buộc lý do |
| `pending` | `sua` | `pending` | Sinh viên đề xuất |
| `rejected` | `sua` | `pending` | Sinh viên sửa và gửi lại |
| `approved` | `bat_dau` | `in_progress` | Tự động khi nhóm nộp bản báo cáo đầu tiên |
| `in_progress` | `du_dieu_kien` | `completed` | Tự động khi GVHD duyệt bản nộp làm **mọi mốc bắt buộc** của lớp đều có bản được duyệt |
| `completed` | `chot_nghiem_thu` | `accepted` | Thư ký khoa, cần ít nhất một lượt chấm |
| `accepted` | — | Không cho phép | Trạng thái cuối |

`completed` nghĩa là **đủ điều kiện nghiệm thu** (hội đồng mới thấy hồ sơ từ đây); `accepted` nghĩa là **đã nghiệm thu**. Hành động không có trong bảng trả `422 INVALID_TRANSITION`.

## 3. Máy trạng thái báo cáo mốc

| Trạng thái hiện tại | Hành động | Trạng thái mới |
|---|---|---|
| `pending` | `duyet` | `approved` |
| `pending` | `yeu_cau_bo_sung` | `revision_requested` |
| `revision_requested` | `nop_lai` | `pending` (tạo bản ghi mới, bản cũ giữ nguyên) |
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

Moi chuyen trang thai deu duoc ghi nhat ky trong cung giao dich:
    PROPOSE_TOPIC, APPROVE_TOPIC, REJECT_TOPIC, RESUBMIT_TOPIC
```

Luồng báo cáo mốc (kèm chuyển trạng thái đề tài tự động):

```text
HAM NOP_BAO_CAO(deTaiId, mocId, tep, nguoiGoi):
    kiem tra quyen (nhom truong), moc con han, tep hop le
    BAT DAU GIAO DICH
        deTai <- SELECT ... FROM de_tai WHERE id = deTaiId FOR UPDATE      -- khoa 1: de tai
        NEU moc da co ban nop dang cho / da duyet: NEM 409
        ghi bao_cao_moc (pending), ghi nhat ky SUBMIT_REPORT
        NEU deTai.trang_thai = approved:
            deTai.trang_thai <- CHUYEN_TRANG_THAI(approved, bat_dau)        -- in_progress
            ghi nhat ky START_TOPIC
    KET THUC GIAO DICH

HAM GVHD_DUYET(baoCaoId, nhanXet, nguoiGoi):
    BAT DAU GIAO DICH
        deTai  <- khoa dong de tai cua bao cao (FOR UPDATE)               -- khoa 1: de tai
        baoCao <- khoa dong bao cao (FOR UPDATE)                           -- khoa 2: bao cao
        kiem tra nguoiGoi la GVHD phu trach
        baoCao.trang_thai <- CHUYEN_TRANG_THAI(baoCao.trang_thai, duyet)
        ghi nhat ky APPROVE_REPORT, thong bao nhom truong
        NEU deTai.trang_thai = in_progress
            VA canCo = so moc bat buoc cua lop > 0
            VA so moc bat buoc da co ban duoc duyet >= canCo:
            deTai.trang_thai <- CHUYEN_TRANG_THAI(in_progress, du_dieu_kien)  -- completed
            ghi nhat ky TOPIC_READY, thong bao nhom truong
    KET THUC GIAO DICH
```

Hai hàm khóa theo **cùng thứ tự** (đề tài trước, báo cáo sau) để hai giao dịch không chờ nhau vòng tròn.

Luồng 3 – chấm điểm nghiệm thu (UC06):

```text
HAM CHAM_DIEM(deTaiId, diem, nhanXet, nguoiGoi):
    NEU diem < 0 HOAC diem > 10: NEM 422 DIEM_NGOAI_KHOANG
    BAT DAU GIAO DICH
        deTai <- SELECT ... FROM de_tai WHERE id = deTaiId FOR UPDATE
        NEU nguoiGoi khong co vai tro HOI_DONG: NEM 403
        NEU deTai.trang_thai <> completed: NEM 422 INVALID_TRANSITION
        NEU nguoiGoi da cham de tai nay: NEM 409 CONFLICT
        ghi diem_nghiem_thu, ghi nhat ky GRADE_TOPIC
    KET THUC GIAO DICH
    -- UNIQUE (de_tai_id, hoi_dong_id) o CSDL la lop chan cuoi, loi trung cung tra 409

HAM CHOT_NGHIEM_THU(deTaiId, nguoiGoi):
    BAT DAU GIAO DICH
        deTai <- SELECT ... FROM de_tai WHERE id = deTaiId FOR UPDATE
        NEU nguoiGoi khong co vai tro THU_KY_KHOA: NEM 403
        moi <- CHUYEN_TRANG_THAI(deTai.trang_thai, chot_nghiem_thu)      -- chi tu completed
        (soLuot, diemTB) <- COUNT(*), ROUND(AVG(diem), 2) cua de tai
        NEU soLuot < 1: NEM 422 CHUA_CO_DIEM
        deTai.trang_thai <- moi                                           -- accepted
        ghi nhat ky ACCEPT_TOPIC (kem diem TB), thong bao nhom truong va GVHD
    KET THUC GIAO DICH

HAM XEM_DIEM(deTaiId, nguoiGoi):
    thu ky khoa, hoi dong: xem bat cu luc nao
    sinh vien trong nhom, GVHD phu trach: chi thay diem khi deTai.trang_thai = accepted
    nguoi khac: 403
```

`CHAM_DIEM` và `CHOT_NGHIEM_THU` cùng khóa dòng đề tài, nên một lượt chấm đến sau khi đã chốt sẽ thấy `accepted` và bị từ chối; còn lệnh chốt luôn thấy đủ các lượt chấm đã ghi trước nó.

Nhật ký luồng 3: `START_TOPIC`, `TOPIC_READY`, `GRADE_TOPIC`, `ACCEPT_TOPIC`. Lịch sử đề tài (`GET /de-tai/{id}/lich-su`) đọc các dòng này nên hiện đủ `pending → approved → in_progress → completed → accepted`.

## 5. API luồng 3

| Phương thức | Đường dẫn | Mã chức năng | Vai trò |
|---|---|---|---|
| GET | `/api/v1/nghiem-thu/ho-so?trang_thai=completed` | F4.1 | Hội đồng |
| GET | `/api/v1/de-tai/{id}/ho-so-nghiem-thu` | F4.2 | Hội đồng |
| POST | `/api/v1/de-tai/{id}/diem-nghiem-thu` `{diem, nhan_xet}` | F4.3 | Hội đồng |
| GET | `/api/v1/de-tai/{id}/diem-nghiem-thu` | F4.4 | Cả 4 vai trò, Service kiểm phạm vi |
| POST | `/api/v1/de-tai/{id}/chot-nghiem-thu` | F4.5 (mới) | Thư ký khoa |

## 6. Kiểm chứng

Các ca kiểm thử đơn vị trong `tests/unit/TrangThaiWorkflowTest.php` (dùng đúng bảng `DeTaiService::CHUYEN_TRANG_THAI`) kiểm tra:

1. `pending -> approved` khi duyệt đề tài.
2. `pending -> rejected -> pending` khi từ chối rồi sửa lại.
3. Không cho phép duyệt lại đề tài đã `approved`.
4. `approved -> in_progress -> completed -> accepted`, `accepted` không còn hành động nào.
5. Không chốt nghiệm thu khi đề tài còn `in_progress`.
6. Không chốt lại đề tài đã `accepted`.
7. `pending -> revision_requested -> pending` với báo cáo mốc.
8. Không cho phép `nop_lai` khi báo cáo chưa ở `revision_requested`.

Hai API lịch sử trạng thái:

- `GET /api/v1/de-tai/{id}/lich-su`
- `GET /api/v1/bao-cao-moc/{id}/lich-su`

Cả hai dùng quyền chức năng `F3.10` và tiếp tục kiểm tra quyền trên đúng đối tượng ở Service để tránh đọc lịch sử của đối tượng không thuộc phạm vi người dùng. Lịch sử chỉ lấy các dòng nhật ký có hành động đổi trạng thái và khớp đúng mã đối tượng (mã 1 không khớp 14, 10, 100…).

Chạy thử đầu–cuối trên máy (33 ca, đều đạt): tạo lớp + 2 mốc bắt buộc + 1 mốc tự chọn → đề xuất → phân công → duyệt → nộp (`in_progress`) → duyệt mốc 1 (vẫn `in_progress`) → mốc 2 bị yêu cầu bổ sung rồi nộp lại, duyệt (`completed`) → điểm 11 / −1 / chữ bị 422 → GVHD chấm bị 403 → chốt khi chưa có điểm 422 → hội đồng 1 chấm 8, chấm lại 409 → sinh viên chưa thấy điểm → hội đồng 2 gửi 10 yêu cầu chấm cùng lúc: 1×201 + 9×409, 1 dòng điểm → thư ký chốt (`accepted`, điểm TB 7,5) → chốt lại / chấm thêm 422 → sinh viên, GVHD thấy điểm → lịch sử đủ 5 trạng thái.
