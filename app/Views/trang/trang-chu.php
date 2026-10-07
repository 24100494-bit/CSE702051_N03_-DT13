<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trang chủ – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</title>
    <?= view('trang/_kieu-trang-chu') ?>
</head>
<body>
<header class="dau-trang">
    <div>
        <p class="phu">Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</p>
        <h1>Xin chào, <?= esc($hoSo['ho_ten']) ?></h1>
        <p class="phu"><?= esc(implode(', ', $dsVaiTro)) ?> · <?= esc($hoSo['ten_dang_nhap']) ?> · <?= esc($hoSo['email']) ?></p>
    </div>
    <button type="button" id="nut-dang-xuat" class="nut-phu">Đăng xuất</button>
</header>

<main id="ung-dung" class="noi-dung"
      data-vai-tro="<?= esc(implode(',', $hoSo['vai_tro']), 'attr') ?>"
      data-id="<?= esc((string) $hoSo['id'], 'attr') ?>">
    <p id="trang-thai-chung" class="loi" role="alert" aria-live="assertive"></p>

    <section class="the" aria-labelledby="tieu-de-thong-bao">
        <h2 id="tieu-de-thong-bao">Thông báo <span id="so-chua-doc" class="nhan"></span></h2>
        <div id="ds-thong-bao"></div>
    </section>

    <!-- Cac khoi theo vai tro do trang-chu.js dung tu API -->
    <div id="khoi-vai-tro"></div>

    <section class="the" aria-labelledby="tieu-de-ho-so">
        <h2 id="tieu-de-ho-so">Hồ sơ cá nhân</h2>
        <form id="form-ho-so" class="luoi-form">
            <label>Họ tên <input name="ho_ten" value="<?= esc($hoSo['ho_ten'], 'attr') ?>" maxlength="100" required></label>
            <label>Email <input name="email" type="email" value="<?= esc($hoSo['email'], 'attr') ?>" maxlength="100" required></label>
            <label>Số điện thoại <input name="so_dien_thoai" value="<?= esc((string) ($hoSo['so_dien_thoai'] ?? ''), 'attr') ?>" maxlength="15"></label>
            <button type="submit">Lưu hồ sơ</button>
        </form>
        <form id="form-mat-khau" class="luoi-form">
            <label>Mật khẩu cũ <input name="mat_khau_cu" type="password" autocomplete="current-password" required></label>
            <label>Mật khẩu mới <input name="mat_khau_moi" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
            <button type="submit">Đổi mật khẩu</button>
        </form>
        <p class="ket-qua" aria-live="polite"></p>
    </section>
</main>

<script src="/assets/trang-chu.js?v=20261007"></script>
</body>
</html>
