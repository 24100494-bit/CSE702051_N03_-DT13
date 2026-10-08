<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThongKeRepository;
use DateTimeImmutable;

/**
 * TANG NGHIEP VU (Service) - ba bao cao tong hop (K7): F5.1 xem, F5.2 xuat CSV.
 * Thu ky khoa xem toan khoa; GVHD chi xem de tai minh huong dan. Xuat tep chi thu ky khoa.
 */
class ThongKeService
{
    /** Loai bao cao -> [ham repository, tieu de, cac cot [khoa => [nhan, la so]]] */
    public const BAO_CAO = [
        'de-tai-theo-lop' => [
            'ham'     => 'deTaiTheoLop',
            'tieu_de' => 'Đề tài theo lớp học phần và trạng thái',
            'cot'     => [
                'ma_lop'                  => ['Mã lớp', false],
                'ten_lop'                 => ['Tên lớp', false],
                'nam_hoc'                 => ['Năm học', false],
                'hoc_ky'                  => ['Học kỳ', false],
                'tong_de_tai'             => ['Tổng đề tài', true],
                'cho_duyet'               => ['Chờ duyệt', true],
                'da_duyet'                => ['Đã duyệt', true],
                'bi_tu_choi'              => ['Bị từ chối', true],
                'dang_thuc_hien'          => ['Đang thực hiện', true],
                'du_dieu_kien_nghiem_thu' => ['Đủ điều kiện nghiệm thu', true],
                'da_nghiem_thu'           => ['Đã nghiệm thu', true],
                'chua_co_gvhd'            => ['Chưa có GVHD', true],
                'so_sinh_vien'            => ['Số sinh viên', true],
            ],
        ],
        'tien-do-nop' => [
            'ham'     => 'tienDoNop',
            'tieu_de' => 'Tiến độ nộp báo cáo theo mốc thời gian',
            'cot'     => [
                'ma_lop'            => ['Mã lớp', false],
                'ten_moc'           => ['Mốc', false],
                'han_nop'           => ['Hạn nộp', false],
                'bat_buoc'          => ['Bắt buộc', true],
                'so_de_tai_can_nop' => ['Cần nộp', true],
                'so_de_tai_da_nop'  => ['Đã nộp', true],
                'chua_nop'          => ['Chưa nộp', true],
                'nop_dung_han'      => ['Nộp đúng hạn', true],
                'nop_tre'           => ['Nộp trễ', true],
                'cho_duyet'         => ['Chờ duyệt', true],
                'da_duyet'          => ['Đã duyệt', true],
                'can_bo_sung'       => ['Cần bổ sung', true],
                'qua_han'           => ['Quá hạn', true],
                'ty_le_nop_pct'     => ['Tỷ lệ nộp (%)', true],
            ],
        ],
        'gvhd' => [
            'ham'     => 'theoGvhd',
            'tieu_de' => 'Tải hướng dẫn và kết quả nghiệm thu theo GVHD',
            'cot'     => [
                'giang_vien'              => ['Giảng viên', false],
                'so_de_tai_huong_dan'     => ['Số đề tài hướng dẫn', true],
                'cho_duyet'               => ['Chờ duyệt', true],
                'bi_tu_choi'              => ['Bị từ chối', true],
                'dang_thuc_hien'          => ['Đang thực hiện', true],
                'du_dieu_kien_nghiem_thu' => ['Đủ điều kiện nghiệm thu', true],
                'da_nghiem_thu'           => ['Đã nghiệm thu', true],
                'diem_trung_binh'         => ['Điểm trung bình', true],
                'diem_cao_nhat'           => ['Điểm cao nhất', true],
                'diem_thap_nhat'          => ['Điểm thấp nhất', true],
            ],
        ],
    ];

    private ThongKeRepository $repo;
    private NhatKyHeThongRepository $nhatKyRepo;

    public function __construct(ThongKeRepository $repo, NhatKyHeThongRepository $nhatKyRepo)
    {
        $this->repo       = $repo;
        $this->nhatKyRepo = $nhatKyRepo;
    }

    /** F5.1 - mot bao cao trong khoang [tuNgay, denNgay] (ngay dang Y-m-d, tinh tron ngay) */
    public function xem(string $loai, ?string $tuNgay, ?string $denNgay, array $currentUser): array
    {
        $dinhNghia = $this->dinhNghia($loai);
        [$tu, $den] = $this->docKhoang($tuNgay, $denNgay);

        if ($this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
            $gvhdId = null;
        } elseif ($this->coVaiTro($currentUser, 'GVHD')) {
            $gvhdId = (int) $currentUser['id'];
        } else {
            throw new ForbiddenException('Ban khong co quyen xem bao cao thong ke');
        }

        $dong = $this->repo->{$dinhNghia['ham']}($tu->format('Y-m-d 00:00:00'), $den->modify('+1 day')->format('Y-m-d 00:00:00'), $gvhdId);

        // GVHD: bo dong khong lien quan (lop / moc khong co de tai cua minh)
        if ($gvhdId !== null && $loai === 'de-tai-theo-lop') {
            $dong = array_values(array_filter($dong, static fn ($d) => (int) $d['tong_de_tai'] > 0));
        }
        if ($gvhdId !== null && $loai === 'tien-do-nop') {
            $dong = array_values(array_filter($dong, static fn ($d) => (int) $d['so_de_tai_can_nop'] > 0));
        }

        return [
            'bao_cao'  => $loai,
            'tieu_de'  => $dinhNghia['tieu_de'],
            'tu_ngay'  => $tu->format('Y-m-d'),
            'den_ngay' => $den->format('Y-m-d'),
            'pham_vi'  => $gvhdId === null ? 'toan_khoa' : 'gvhd',
            'cot'      => array_map(static fn ($k, $c) => ['khoa' => $k, 'nhan' => $c[0], 'la_so' => $c[1]], array_keys($dinhNghia['cot']), $dinhNghia['cot']),
            'dong'     => array_map(fn ($d) => $this->epKieu($d, $dinhNghia['cot']), $dong),
        ];
    }

    /**
     * F5.2 - xuat CSV (UTF-8 co BOM de Excel doc dung tieng Viet, tach bang dau phay, chuoi duoc bao ngoac kep).
     * Tra ve [ten tep, noi dung].
     */
    public function xuatCsv(string $loai, ?string $tuNgay, ?string $denNgay, array $currentUser): array
    {
        if (!$this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
            throw new ForbiddenException('Chi thu ky khoa duoc xuat bao cao');
        }

        $baoCao = $this->xem($loai, $tuNgay, $denNgay, $currentUser);

        $f = fopen('php://temp', 'w+');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, array_column($baoCao['cot'], 'nhan'), ',', '"', '');
        foreach ($baoCao['dong'] as $d) {
            fputcsv($f, array_map(fn ($c) => $this->oAnToan($d[$c['khoa']]), $baoCao['cot']), ',', '"', '');
        }
        rewind($f);
        $noiDung = stream_get_contents($f);
        fclose($f);

        $this->nhatKyRepo->ghiNhan(
            (int) $currentUser['id'],
            'EXPORT_REPORT',
            'Xuat bao cao ' . $loai . ' tu ' . $baoCao['tu_ngay'] . ' den ' . $baoCao['den_ngay'] . ' (' . count($baoCao['dong']) . ' dong)',
            $currentUser['ip'] ?? null
        );

        return ['bao-cao-' . $loai . '_' . $baoCao['tu_ngay'] . '_' . $baoCao['den_ngay'] . '.csv', $noiDung];
    }

    private function dinhNghia(string $loai): array
    {
        if (!isset(self::BAO_CAO[$loai])) {
            throw new NotFoundException('Khong co bao cao ' . $loai);
        }

        return self::BAO_CAO[$loai];
    }

    /** Mac dinh ca nam hien tai; ngay sai dinh dang hoac tu > den tra 422 */
    private function docKhoang(?string $tuNgay, ?string $denNgay): array
    {
        $nam = date('Y');
        $tu  = $this->docNgay($tuNgay ?: $nam . '-01-01', 'tu_ngay');
        $den = $this->docNgay($denNgay ?: $nam . '-12-31', 'den_ngay');

        if ($tu > $den) {
            throw new ValidationException([['field' => 'tu_ngay, den_ngay', 'issue' => 'Tu ngay phai truoc hoac bang den ngay']]);
        }

        return [$tu, $den];
    }

    private function docNgay(string $giaTri, string $truong): DateTimeImmutable
    {
        $ngay = DateTimeImmutable::createFromFormat('!Y-m-d', $giaTri);
        if (!$ngay || $ngay->format('Y-m-d') !== $giaTri) {
            throw new ValidationException([['field' => $truong, 'issue' => 'Dinh dang YYYY-MM-DD']]);
        }

        return $ngay;
    }

    /** Chi giu cac cot khai bao, doi cot so sang int/float cho bieu do */
    private function epKieu(array $dong, array $cot): array
    {
        $kq = [];
        foreach ($cot as $khoa => [, $laSo]) {
            $v = $dong[$khoa] ?? null;
            $kq[$khoa] = ($laSo && $v !== null) ? $v + 0 : $v;
        }

        return $kq;
    }

    /** Chong chen cong thuc khi mo bang Excel: chuoi bat dau bang = + - @ duoc them dau nhay don */
    private function oAnToan($v)
    {
        if (is_string($v) && $v !== '' && !is_numeric($v) && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $v;
        }

        return $v;
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
