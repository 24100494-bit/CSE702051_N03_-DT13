<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trang chủ – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</title>
    <meta name="description" content="Trang làm việc theo vai trò: đề tài, báo cáo theo mốc, nghiệm thu, thông báo và hồ sơ cá nhân.">
    <meta name="robots" content="noindex, nofollow">
    <?= view('trang/_kieu-trang-chu') ?>
</head>
<body>
<a class="bo-qua" href="#ung-dung">Bỏ qua menu, tới nội dung chính</a>
<div class="bo-cuc">
    <aside id="thanh-ben" class="thanh-ben" aria-label="Menu chức năng">
        <div class="thanh-ben-trong">
        <div class="thuong-hieu">
            <span class="logo" data-icon="logo"></span>
            <span><strong>Quản lý Đồ án</strong><small>và Đề tài Nghiên cứu</small></span>
        </div>
        <nav id="menu" aria-label="Chức năng"></nav>
        <div class="the-nguoi-dung">
            <span class="anh-dai-dien" aria-hidden="true" id="chu-viet-tat"></span>
            <span class="ten-nguoi-dung">
                <strong><?= esc($hoSo['ho_ten']) ?></strong>
                <small><?= esc(implode(', ', $dsVaiTro)) ?></small>
            </span>
        </div>
        </div>
    </aside>
    <div class="lop-phu" id="lop-phu" hidden></div>

    <div class="vung-chinh">
        <header class="thanh-tren">
            <button type="button" id="nut-menu" class="nut-icon nut-menu" aria-controls="thanh-ben" aria-expanded="false" aria-label="Mở menu"><span data-icon="menu"></span></button>
            <div class="tieu-de-trang">
                <p id="duong-dan" class="duong-dan"></p>
                <h1 id="tieu-de-trang" tabindex="-1">Tổng quan</h1>
            </div>
            <a href="#thong-bao" class="nut-icon nut-chuong" id="nut-chuong" aria-label="Thông báo"><span data-icon="chuong"></span><span id="so-chuong" class="cham-so" hidden></span></a>
            <button type="button" id="nut-dang-xuat" class="nut-phu nut-dang-xuat"><span data-icon="dang-xuat"></span><span class="chu-nut">Đăng xuất</span></button>
        </header>

        <main id="ung-dung" class="noi-dung" tabindex="-1"
              data-vai-tro="<?= esc(implode(',', $hoSo['vai_tro']), 'attr') ?>"
              data-id="<?= esc((string) $hoSo['id'], 'attr') ?>"
              data-ten="<?= esc($hoSo['ho_ten'], 'attr') ?>">
            <p id="trang-thai-chung" class="loi" role="alert" aria-live="assertive"></p>

            <div class="trang" id="trang-thong-bao" hidden>
                <section class="the" aria-labelledby="tieu-de-thong-bao">
                    <h2 id="tieu-de-thong-bao">Thông báo của bạn <span id="so-chua-doc" class="nhan"></span></h2>
                    <div id="ds-thong-bao"></div>
                </section>
            </div>

            <!-- Cac trang theo vai tro do trang-chu.js dung tu API -->
            <div id="khoi-vai-tro"></div>

            <div class="trang" id="trang-ho-so" hidden>
                <section class="the the-gioi-thieu" aria-label="Thông tin tài khoản">
                    <span class="anh-dai-dien lon" aria-hidden="true" id="chu-viet-tat-lon"></span>
                    <div>
                        <p class="ten-lon"><?= esc($hoSo['ho_ten']) ?></p>
                        <p class="phu"><?= esc(implode(', ', $dsVaiTro)) ?> · <?= esc($hoSo['ten_dang_nhap']) ?> · <?= esc($hoSo['email']) ?></p>
                    </div>
                </section>
                <section class="the" aria-labelledby="tieu-de-ho-so">
                    <h2 id="tieu-de-ho-so">Thông tin cá nhân</h2>
                    <form id="form-ho-so" class="luoi-form">
                        <label>Họ tên <input name="ho_ten" value="<?= esc($hoSo['ho_ten'], 'attr') ?>" maxlength="100" required></label>
                        <label>Email <input name="email" type="email" value="<?= esc($hoSo['email'], 'attr') ?>" maxlength="100" required></label>
                        <label>Số điện thoại <input name="so_dien_thoai" value="<?= esc((string) ($hoSo['so_dien_thoai'] ?? ''), 'attr') ?>" maxlength="15"></label>
                        <button type="submit">Lưu hồ sơ</button>
                    </form>
                    <h3 class="tieu-de-phu">Đổi mật khẩu</h3>
                    <form id="form-mat-khau" class="luoi-form">
                        <label>Mật khẩu cũ <input name="mat_khau_cu" type="password" autocomplete="current-password" required></label>
                        <label>Mật khẩu mới <input name="mat_khau_moi" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></label>
                        <button type="submit">Đổi mật khẩu</button>
                    </form>
                    <p class="ket-qua" aria-live="polite"></p>
                </section>
            </div>
        </main>
        <?= view('trang/_chan-trang') ?>
    </div>
</div>

<script src="/assets/trang-chu.js?v=20261105"></script>
</body>
</html>
