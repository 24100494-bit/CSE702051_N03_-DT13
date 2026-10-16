# Kiểm thử API tự động – bộ 25 ca (Buổi 9)

| Tệp | Nội dung |
|---|---|
| `DT13-25-ca.postman_collection.json` | Bộ sưu tập Postman, 25 thư mục TC-01 → TC-25, chạy theo đúng thứ tự |
| `may-cuc-bo.postman_environment.json` | Biến môi trường: địa chỉ API và tên các tài khoản thử; mật khẩu để trống |
| `bao-cao-thu.pdf`, `tep-gia.pdf` | Tệp nộp hợp lệ (TC-10, TC-12) và tệp thực thi đổi đuôi `.pdf` (TC-09) |

Bảng ca kiểm thử (mong đợi, kết quả thực tế, kết luận): sheet `Bo 25 ca kiem thu` trong `Ma tran phan quyen.xlsx`.

## Chạy

Cần Node.js và `npm install -g newman newman-reporter-html`. Chạy từ thư mục `tests/api`, máy chủ đã nạp `schema.sql`, `seed.sql` và các tệp cập nhật:

```bash
newman run DT13-25-ca.postman_collection.json -e may-cuc-bo.postman_environment.json \
  --env-var base_url=http://localhost:8080/api/v1 --env-var "mat_khau=<mật khẩu tài khoản thử>" \
  --working-dir . --reporters cli,html --reporter-html-export ../../docs/Buoi09/bao-cao-kiem-thu-api.html
```

- Mỗi lần chạy tự tạo lớp học phần, mốc, sinh viên và đề tài mới (mã `PM-<mã lần chạy>`), nên chạy lại nhiều lần không bị trùng dữ liệu.
- TC-25 nhập sai mật khẩu 6 lần nên địa chỉ IP bị chặn đăng nhập 15 phút (BM10). Muốn chạy lại ngay thì phải chờ hết 15 phút.
