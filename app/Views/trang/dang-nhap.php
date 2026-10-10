<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</title>
    <?= view('trang/_kieu') ?>
</head>
<body>
<main class="khung">
    <h1>Hệ thống Quản lý Đồ án<br>và Đề tài Nghiên cứu</h1>
    <p class="phu">Đăng nhập để tiếp tục</p>

    <form id="form-dang-nhap" novalidate>
        <label for="ten_dang_nhap">Tên đăng nhập</label>
        <input id="ten_dang_nhap" name="ten_dang_nhap" type="text" autocomplete="username" required autofocus>

        <label for="mat_khau">Mật khẩu</label>
        <input id="mat_khau" name="mat_khau" type="password" autocomplete="current-password" required>

        <p id="thong-bao" class="loi" role="alert" aria-live="assertive"></p>

        <button type="submit" id="nut-dang-nhap">Đăng nhập</button>
    </form>
</main>

<script src="/assets/dang-nhap.js?v=20261009"></script>
</body>
</html>
