<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</title>
    <meta name="description" content="Đăng nhập Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu dành cho sinh viên, giảng viên hướng dẫn, thư ký khoa và hội đồng nghiệm thu; khôi phục mật khẩu bằng mã do thư ký khoa cấp.">
    <?= view('trang/_kieu') ?>
</head>
<body>
<main class="khung">
    <h1>Hệ thống Quản lý Đồ án<br>và Đề tài Nghiên cứu</h1>
    <p class="phu">Đăng nhập để tiếp tục · <a href="/">Xem đề tài đã duyệt</a></p>

    <form id="form-dang-nhap" novalidate>
        <label for="ten_dang_nhap">Tên đăng nhập</label>
        <input id="ten_dang_nhap" name="ten_dang_nhap" type="text" autocomplete="username" required autofocus>

        <label for="mat_khau">Mật khẩu</label>
        <input id="mat_khau" name="mat_khau" type="password" autocomplete="current-password" required>

        <p id="thong-bao" class="loi" role="alert" aria-live="assertive"></p>

        <button type="submit" id="nut-dang-nhap">Đăng nhập</button>
        <p class="lien-ket"><button type="button" id="mo-quen" class="nut-chu">Quên mật khẩu?</button></p>
    </form>

    <section id="khoi-quen" hidden aria-labelledby="tieu-de-quen">
        <h2 id="tieu-de-quen">Quên mật khẩu</h2>
        <p class="phu">Bước 1: gửi yêu cầu, thư ký khoa sẽ kiểm tra rồi cấp mã đặt lại cho bạn.</p>
        <form id="form-yeu-cau" novalidate>
            <label for="email_quen">Email tài khoản</label>
            <input id="email_quen" name="email" type="email" autocomplete="email" maxlength="100" required>
            <p id="tb-yeu-cau" class="loi" role="status" aria-live="polite"></p>
            <button type="submit" id="nut-yeu-cau">Gửi yêu cầu</button>
        </form>

        <p class="phu buoc">Bước 2: đã có mã từ thư ký khoa thì đặt mật khẩu mới.</p>
        <form id="form-dat-lai" novalidate>
            <label for="ma_dat_lai">Mã đặt lại</label>
            <input id="ma_dat_lai" name="token" type="text" autocomplete="one-time-code" maxlength="20" required>
            <label for="mat_khau_moi">Mật khẩu mới</label>
            <input id="mat_khau_moi" name="mat_khau_moi" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
            <label for="mat_khau_moi_2">Nhập lại mật khẩu mới</label>
            <input id="mat_khau_moi_2" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
            <p id="tb-dat-lai" class="loi" role="alert" aria-live="assertive"></p>
            <button type="submit" id="nut-dat-lai">Đặt lại mật khẩu</button>
        </form>
        <p class="lien-ket"><button type="button" id="ve-dang-nhap" class="nut-chu">Quay lại đăng nhập</button></p>
    </section>
</main>
<?= view('trang/_chan-trang') ?>

<script src="/assets/dang-nhap.js?v=20261016"></script>
</body>
</html>
