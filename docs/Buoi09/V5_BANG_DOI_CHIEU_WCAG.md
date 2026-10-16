# Bảng đối chiếu tiêu chí WCAG 2.1 mức AA (Buổi 9 – V5)

**Hệ thống:** Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu (đề tài DT13) – Nhóm 13
**Người thực hiện:** Nguyễn Minh Hải (V5 – triển khai và tài liệu)
**Ngày kiểm tra:** 16/10/2026. Lighthouse chạy trên https://htquanlydavadtnc.freepage.cc; kịch bản tự động chạy trên bản cục bộ cùng mã nguồn với bản trực tuyến
**Phạm vi:** ba trang giao diện – trang công khai `/`, trang đăng nhập `/dang-nhap`, trang làm việc theo vai trò `/trang-chu`
**Căn cứ:** Phụ lục 09, mục C của Thông báo yêu cầu dự án; Tài liệu hướng dẫn kỹ thuật mục 7.3

## Cách kiểm tra

- **Lighthouse** trong công cụ nhà phát triển của Chrome, chạy trên trang công khai trực tuyến (Ảnh 30).
- **Kịch bản tự động trên Chrome**, chạy với cả bốn vai trò: sinh viên, GVHD, thư ký khoa, hội đồng. Kịch bản thực hiện:
  - đếm thẻ h1 và dò chỗ nhảy cấp tiêu đề;
  - kiểm tra nhãn của mọi ô nhập, kể cả ô nằm trong khối chỉ hiện sau khi bấm nút;
  - tính tỷ lệ tương phản của mọi phần tử chứa chữ theo công thức độ chói tương đối của WCAG;
  - mô phỏng phím Tab và Enter;
  - thu hẹp khung nhìn về 320 px;
  - gửi biểu mẫu sai để kiểm tra cách báo lỗi.
- **Truy cập trực tiếp** `/robots.txt` và `/sitemap.xml` trên tên miền của nhóm.

## Bảng đối chiếu

| STT | Tiêu chí (Phụ lục 09, mục C) | Tiêu chí WCAG 2.1 | Cách đáp ứng trong hệ thống | Kết quả kiểm tra | Đánh giá |
|:---:|---|---|---|---|:---:|
| 1 | Mỗi trang có thẻ tiêu đề và thẻ mô tả riêng | 2.4.2 Tiêu đề trang (A) | Ba trang có `title` và `meta description` riêng. Từ trang 2 của kết quả, tiêu đề thêm "– Trang n". Trang công khai có 6 thẻ Open Graph và thẻ `canonical`. Trang làm việc khai báo `noindex, nofollow` | Ba tiêu đề và ba mô tả khác nhau | Đạt |
| 2 | Đường dẫn thân thiện, có bản đồ trang và tệp khai báo | 2.4.5 Nhiều cách tiếp cận (AA) | Đường dẫn `/`, `/dang-nhap`, `/trang-chu`; tra cứu và phân trang dùng `/?q=…&trang=2`, liên kết trang có `rel="prev"` và `rel="next"`. Tệp `robots.txt` chặn `/api/` và `/trang-chu`, khai báo vị trí bản đồ trang; tệp `sitemap.xml` liệt kê 2 trang công khai bằng HTTPS | Hai tệp mở được trên tên miền của nhóm | Đạt |
| 3 | Cấu trúc tiêu đề theo thứ bậc; dùng thẻ ngữ nghĩa | 1.3.1 Thông tin và quan hệ (A); 2.4.1 Bỏ qua khối (A); 2.4.6 Tiêu đề và nhãn (AA); 3.1.1 Ngôn ngữ trang (A) | Mỗi trang có một thẻ h1. Trang công khai có `header`, `nav`, `main`, `footer` và liên kết "Bỏ qua đến nội dung chính"; trang đăng nhập và trang làm việc có `main`, `footer` (trang làm việc có thêm `header`). Cả ba trang khai báo `lang="vi"` | Cả ba trang: 1 thẻ h1, không nhảy cấp tiêu đề, không trùng `id` | Đạt |
| 4 | Mọi hình ảnh mang thông tin có văn bản thay thế | 1.1.1 Nội dung phi văn bản (A) | Giao diện không dùng thẻ `img`. Biểu đồ thống kê vẽ bằng SVG có `role="img"` và `aria-label` nêu tên biểu đồ, kèm bảng số liệu chi tiết. Nút và liên kết đều có tên; liên kết tải tệp có `aria-label` nêu tên tệp | Không có thẻ `img`; biểu đồ SVG có `aria-label` (rà mã nguồn); 0 nút hoặc liên kết không có tên | Đạt |
| 5 | Tỷ lệ tương phản đạt mức AA | 1.4.3 Tương phản tối thiểu (AA) | Màu chữ trên nền đạt từ 4,5:1. Nút phân trang khi bị khóa được nâng từ 1,39:1 lên 7:1 | 0 phần tử chữ dưới 4,5:1 (chữ lớn: dưới 3:1) trên cả ba trang | Đạt |
| 6 | Chức năng chính điều khiển được bằng bàn phím; có chỉ báo tiêu điểm | 2.1.1 Bàn phím (A); 2.1.2 Không bẫy bàn phím (A); 2.4.3 Thứ tự tiêu điểm (A); 2.4.7 Hiển thị tiêu điểm (AA) | Viền tiêu điểm 3 px cho ô nhập, nút và liên kết; không dùng `outline: none` trên phần tử điều khiển. Ô tên đăng nhập nhận tiêu điểm ngay khi mở trang | Đăng nhập chỉ bằng bàn phím thành công; mở "Báo cáo theo mốc" bằng Enter; mọi phần tử nhận tiêu điểm đều có viền | Đạt |
| 7 | Nhãn biểu mẫu liên kết đúng; thông báo lỗi nêu cách khắc phục | 1.3.1 (A); 2.5.3 Nhãn trong tên (A); 3.3.1 Nhận diện lỗi (A); 3.3.2 Nhãn hoặc hướng dẫn (A); 3.3.3 Gợi ý sửa lỗi (AA); 4.1.3 Thông báo trạng thái (AA) | Mọi ô nhập có nhãn hiển thị: `label for` ở trang đăng nhập và trang công khai; thẻ `label` bao ô nhập ở trang làm việc; ô nằm trong bảng dùng tiêu đề cột. Lỗi hiện trong vùng `role="alert"` và nêu cách sửa, ví dụ "Mật khẩu mới từ 8 đến 72 ký tự.". Ô bị báo sai được đặt `aria-invalid`, nối với thông báo bằng `aria-describedby` và nhận lại tiêu điểm | Quét cả bốn vai trò: 0 ô thiếu nhãn hiển thị (152 ô ở vai trò thư ký khoa). Các ca để trống, sai mật khẩu, tạo tài khoản trùng tên và email, thêm lớp trùng mã: ô sai được đánh dấu và nhận tiêu điểm; sửa đúng thì bỏ đánh dấu | Đạt |
| 8 | Giao diện hiển thị đúng trên thiết bị di động | 1.4.10 Dàn lại nội dung (AA) | Có thẻ `viewport`; bố cục co giãn theo chiều rộng màn hình | Ở khung nhìn 320 px, cả ba trang không xuất hiện thanh cuộn ngang | Đạt |
| 9 | Có kết quả đo bằng công cụ kiểm tra tự động kèm ảnh chụp | — | Lighthouse trong công cụ nhà phát triển của Chrome | Trang công khai trực tuyến: Performance 94, Accessibility 100, Best Practices 100, SEO 100 (Ảnh 30) | Đạt |

**Kết quả: 9/9 tiêu chí đạt.**

## Ghi chú

- **Tiêu chí 7 đã được sửa trong Buổi 9.** Lần rà soát đầu, tiêu chí này chỉ đạt một phần vì hai lý do:
  - một số ô nhập ở trang làm việc chỉ có chữ gợi ý hoặc `aria-label`, không có nhãn hiển thị: lọc hồ sơ nghiệm thu, chọn GVHD, mã sinh viên khi thêm thành viên, tệp nộp lại, chọn lớp học phần, nhận xét, tìm người dùng;
  - thông báo lỗi chưa gắn với ô nhập bị sai.

  Bản sửa gồm `public/assets/trang-chu.js`, `public/assets/dang-nhap.js`, hai tệp CSS và bốn view, đã lên web ngày 16/10/2026.
- **Cảnh báo chuyển hướng của Lighthouse.** Lighthouse báo địa chỉ gốc bị chuyển hướng sang `/?i=1`. Đây là bước kiểm tra của trang chống truy cập tự động do InfinityFree chèn vào trước trang của hệ thống, không phải chuyển hướng của ứng dụng.
- **Phạm vi của Lighthouse.** Lighthouse chỉ đo được trang công khai. Trang làm việc cần đăng nhập nên được kiểm bằng kịch bản tự động nêu ở phần "Cách kiểm tra".
- **Hạn chế còn lại.** Một số thông báo lỗi do máy chủ trả về còn viết tiếng Việt không dấu.
