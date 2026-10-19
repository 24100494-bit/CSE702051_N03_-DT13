| Ma | Diem cuoi | So ban ghi | Truoc (ms) | Sau (ms) | Bien phap ap dung | Cai thien |
|---|---|---:|---:|---:|---|---:|
| D1 | `GET /de-tai?lop_hoc_phan_id=1&trang_thai=in_progress&page=1&size=20` | 20084 | 35.2 | 28.2 | Chi muc (lop_hoc_phan_id, trang_thai, created_at): bo sap xep ca tap ket qua | 19.9% |
| D2 | `GET /public/de-tai?page=1` | 20084 | 40.6 | 30.1 | Chi muc (created_at): doc nguoc chi muc thay cho quet toan bang | 25.9% |
| D3 | `GET /thong-ke/tien-do-nop` | 20077 | 64.1 | 45.8 | Chi muc phu (de_tai_id, moc_thoi_gian_id, created_at, id): chi doc chi muc | 28.5% |
| D4 | `GET /nghiem-thu/ho-so?page=1&size=20` | 20084 | 26.7 | 26.0 | V3: gom truy van lap (42 -> 4 truy van) + bo nho dem 10 giay | 2.6% |
| D5 | `GET /nhat-ky-he-thong?hanh_dong=APPROVE_TOPIC` | 20312 | 33.5 | 25.0 | Chi muc (hanh_dong, created_at): chi doc dong cua hanh dong can loc | 25.4% |

Trung vi cua 50 lan goi moi diem cuoi (curl.exe, bo 1 lan lam nong); cung may, cung du lieu, OPcache bat.
