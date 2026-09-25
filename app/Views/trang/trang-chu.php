<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trang chủ – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</title>
    <?= view('trang/_kieu') ?>
</head>
<body>
<main class="khung">
    <p class="phu">Đăng nhập thành công</p>
    <h1>Xin chào, <?= esc($hoSo['ho_ten']) ?></h1>

    <dl class="thong-tin">
        <dt>Vai trò</dt>
        <dd><strong><?= esc(implode(', ', $dsVaiTro)) ?></strong></dd>
        <dt>Tên đăng nhập</dt>
        <dd><?= esc($hoSo['ten_dang_nhap']) ?></dd>
        <dt>Email</dt>
        <dd><?= esc($hoSo['email']) ?></dd>
    </dl>

    <button type="button" id="nut-dang-xuat">Đăng xuất</button>
</main>

<script>
document.getElementById('nut-dang-xuat').addEventListener('click', async () => {
    await fetch('/api/v1/auth/dang-xuat', { method: 'POST', credentials: 'same-origin' });
    window.location.href = '/dang-nhap';
});
</script>
</body>
</html>
