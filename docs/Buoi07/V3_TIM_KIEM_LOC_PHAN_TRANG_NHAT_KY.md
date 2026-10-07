# Buoi 07 - V3: K5 va giao dien K8

## 1. Phan cong theo Phu luc 03 moi

- **K5 - Tim kiem, loc va phan trang:** V3 phu trach hien thuc tim kiem nhieu dieu kien, sap xep va phan trang phia may chu.
- **K8 - Nhat ky he thong:** V3 phu trach **giao dien tra cuu nhat ky**; V4 phu trach **ghi dang nhap thanh cong/that bai trong `XacThucService`**. V3 khong ghi login log.

## 2. K5 - Tim kiem / loc / sap xep / phan trang

API: `GET /api/v1/de-tai`

- `tu_khoa`: tim trong `ten_de_tai`, `mo_ta_pham_vi`.
- `trang_thai`: `pending`, `approved`, `rejected`, `in_progress`, `completed`, `accepted`.
- `lop_hoc_phan_id`: loc theo lop hoc phan.
- `sap_xep`: `created_at`, `ten_de_tai`, `trang_thai`.
- `huong`: `ASC` hoac `DESC`.
- Phan trang server-side voi `page`, `size`; `size` toi da 100.
- Them `id DESC` lam tieu chi sap xep phu de tranh lap/bo sot khi cac ban ghi co cung gia tri sap xep.

Giao dien `trang-chu.js` co: tu khoa, trang thai, lop hoc phan, cot sap xep, huong sap xep, nut xoa bo loc va nut Trang truoc/Trang sau.

Vi du:
`GET /api/v1/de-tai?page=1&size=20&tu_khoa=quan%20ly&trang_thai=in_progress&lop_hoc_phan_id=1&sap_xep=created_at&huong=DESC`

## 3. K8 - Giao dien tra cuu nhat ky

API ho tro giao dien: `GET /api/v1/nhat-ky-he-thong`

- Chi vai tro `thu_ky_khoa` duoc phep tra cuu theo F5.3.
- Giao dien loc theo `nguoi_dung_id`, `hanh_dong`, `tu_khoa`, `tu_ngay`, `den_ngay`.
- Co phan trang server-side.
- Hien thi thoi gian, nguoi dung, hanh dong, chi tiet va dia chi IP.
- `XacThucService` khong ghi login log trong phan V3 nay; viec ghi `LOGIN_SUCCESS`/`LOGIN_FAILED` thuoc V4 theo bang phan cong moi.

## 4. Kiem thu

1. K5: ket hop `tu_khoa + trang_thai + lop_hoc_phan_id`.
2. K5: doi `sap_xep` va `huong`; du lieu trung gia tri sap xep van co tie-breaker `id DESC`.
3. K5: chuyen Trang truoc/Trang sau va doi chieu `total_pages`.
4. K5: `size=1000` bi tu choi voi 422.
5. K8: tai khoan `thu_ky_khoa` xem duoc giao dien tra cuu nhat ky.
6. K8: vai tro khac bi chan boi F5.3 voi 403 khi goi API.
7. K8: loc theo hanh dong va khoang ngay.
