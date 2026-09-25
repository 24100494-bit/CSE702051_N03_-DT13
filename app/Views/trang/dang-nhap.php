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

<script>
document.getElementById('form-dang-nhap').addEventListener('submit', async (e) => {
    e.preventDefault();
    const thongBao = document.getElementById('thong-bao');
    const nut = document.getElementById('nut-dang-nhap');
    const tenDangNhap = document.getElementById('ten_dang_nhap').value.trim();
    const matKhau = document.getElementById('mat_khau').value;

    thongBao.textContent = '';
    if (!tenDangNhap || !matKhau) {
        thongBao.textContent = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
        return;
    }

    nut.disabled = true;
    nut.textContent = 'Đang đăng nhập...';
    try {
        const r = await fetch('/api/v1/auth/dang-nhap', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ten_dang_nhap: tenDangNhap, mat_khau: matKhau }),
        });
        if (r.ok) {
            window.location.href = '/trang-chu';
            return;
        }
        thongBao.textContent = r.status === 401
            ? 'Sai tên đăng nhập hoặc mật khẩu.'
            : 'Không đăng nhập được (mã ' + r.status + '). Vui lòng thử lại.';
    } catch (err) {
        thongBao.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
    }
    nut.disabled = false;
    nut.textContent = 'Đăng nhập';
});
</script>
</body>
</html>
