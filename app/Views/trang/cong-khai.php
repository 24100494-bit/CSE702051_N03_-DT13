<?php
$tieuDe = 'Đề tài đã duyệt – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu';
$moTa   = 'Danh sách công khai các đề tài đồ án và đề tài nghiên cứu đã được giảng viên hướng dẫn phê duyệt, tra cứu theo tên đề tài, lớp học phần và trạng thái thực hiện.';
$trang  = $ketQua['page'];
$soTrang = $ketQua['total_pages'];
$lienKet = static fn (int $t): string => '/?' . http_build_query(array_filter(['q' => $tuKhoa, 'trang' => $t > 1 ? $t : null], static fn ($v) => $v !== null && $v !== ''));
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($trang > 1 ? $tieuDe . ' – Trang ' . $trang : $tieuDe) ?></title>
    <meta name="description" content="<?= esc($moTa, 'attr') ?>">
    <link rel="canonical" href="<?= esc($diaChiGoc . ($trang > 1 ? '?trang=' . $trang : ''), 'attr') ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:site_name" content="Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu">
    <meta property="og:title" content="<?= esc($tieuDe, 'attr') ?>">
    <meta property="og:description" content="<?= esc($moTa, 'attr') ?>">
    <meta property="og:url" content="<?= esc($diaChiGoc, 'attr') ?>">
    <link rel="stylesheet" href="/assets/kieu-cong-khai.css?v=20261014">
</head>
<body>
<a class="bo-qua" href="#noi-dung">Bỏ qua đến nội dung chính</a>
<header class="dau-trang">
    <p class="ten-he-thong">Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu</p>
    <nav aria-label="Điều hướng chính">
        <?php if ($daDangNhap): ?>
            <a class="nut" href="/trang-chu">Vào trang làm việc</a>
        <?php else: ?>
            <a class="nut" href="/dang-nhap">Đăng nhập</a>
        <?php endif ?>
    </nav>
</header>

<main id="noi-dung" class="noi-dung" tabindex="-1">
    <h1>Đề tài đã được duyệt</h1>
    <p class="phu">Các đề tài đồ án và đề tài nghiên cứu đã được giảng viên hướng dẫn phê duyệt. Sinh viên, giảng viên đăng nhập để đề xuất, nộp báo cáo và theo dõi tiến độ.</p>

    <form class="tim-kiem" method="get" action="/" role="search">
        <label for="q">Tìm theo tên hoặc mô tả đề tài</label>
        <div class="hang">
            <input id="q" name="q" type="search" maxlength="100" value="<?= esc($tuKhoa, 'attr') ?>">
            <button type="submit">Tìm</button>
        </div>
    </form>

    <p class="phu" role="status">
        <?php if ($ketQua['total'] > 0): ?>
            Tìm thấy <?= esc((string) $ketQua['total']) ?> đề tài<?= $tuKhoa !== '' ? ' khớp với “' . esc($tuKhoa) . '”' : '' ?> · trang <?= esc((string) $trang) ?>/<?= esc((string) $soTrang) ?>
        <?php else: ?>
            Không có đề tài nào<?= $tuKhoa !== '' ? ' khớp với “' . esc($tuKhoa) . '”' : '' ?>.
        <?php endif ?>
    </p>

    <?php foreach ($ketQua['items'] as $d): ?>
        <article class="de-tai">
            <h2><?= esc($d['ten_de_tai']) ?></h2>
            <p class="thong-tin">
                <span class="nhan nhan-<?= esc($d['trang_thai'], 'attr') ?>"><?= esc($d['ten_trang_thai']) ?></span>
                <?php if ($d['lop_hoc_phan']): ?>
                    <span>Lớp <?= esc($d['lop_hoc_phan']['ma_lop']) ?> – <?= esc($d['lop_hoc_phan']['ten_lop']) ?>, năm học <?= esc((string) $d['lop_hoc_phan']['nam_hoc']) ?>, <?= esc((string) $d['lop_hoc_phan']['hoc_ky']) ?></span>
                <?php endif ?>
            </p>
            <?php if ($d['mo_ta_pham_vi']): ?>
                <p><?= esc($d['mo_ta_pham_vi']) ?></p>
            <?php endif ?>
        </article>
    <?php endforeach ?>

    <?php if ($soTrang > 1): ?>
        <nav class="phan-trang" aria-label="Phân trang">
            <?php if ($trang > 1): ?>
                <a href="<?= esc($lienKet($trang - 1), 'attr') ?>" rel="prev">Trang trước</a>
            <?php endif ?>
            <span aria-current="page">Trang <?= esc((string) $trang) ?>/<?= esc((string) $soTrang) ?></span>
            <?php if ($trang < $soTrang): ?>
                <a href="<?= esc($lienKet($trang + 1), 'attr') ?>" rel="next">Trang sau</a>
            <?php endif ?>
        </nav>
    <?php endif ?>
</main>

<?= view('trang/_chan-trang') ?>
</body>
</html>
