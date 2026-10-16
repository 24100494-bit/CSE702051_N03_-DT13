<?php

namespace App\Services;

use App\Repositories\CongKhaiRepository;

/**
 * TANG NGHIEP VU (Service) - danh sach de tai cong khai (K10, F2.1), khong can dang nhap.
 * Kich thuoc trang toi da SIZE_TOI_DA; tu khoa cat con 100 ky tu.
 */
class CongKhaiService
{
    public const SIZE_MAC_DINH = 10;
    public const SIZE_TOI_DA   = 50;

    private const TEN_TRANG_THAI = [
        'approved'    => 'Đã duyệt',
        'in_progress' => 'Đang thực hiện',
        'completed'   => 'Đủ điều kiện nghiệm thu',
        'accepted'    => 'Đã nghiệm thu',
    ];

    public function __construct(private CongKhaiRepository $repo)
    {
    }

    public function danhSach(int $page, int $size, ?string $tuKhoa): array
    {
        $page   = max(1, $page);
        $size   = $size < 1 ? self::SIZE_MAC_DINH : min($size, self::SIZE_TOI_DA);
        $tuKhoa = $tuKhoa === null ? null : mb_substr(trim($tuKhoa), 0, 100);

        $ketQua = $this->repo->deTaiDaDuyet($page, $size, $tuKhoa);

        return [
            'items'       => array_map(fn (array $d): array => [
                'id'             => (int) $d['id'],
                'ten_de_tai'     => $d['ten_de_tai'],
                'mo_ta_pham_vi'  => $d['mo_ta_pham_vi'],
                'trang_thai'     => $d['trang_thai'],
                'ten_trang_thai' => self::TEN_TRANG_THAI[$d['trang_thai']] ?? $d['trang_thai'],
                'lop_hoc_phan'   => $d['ma_lop'] === null ? null : [
                    'ma_lop'  => $d['ma_lop'],
                    'ten_lop' => $d['ten_lop'],
                    'nam_hoc' => $d['nam_hoc'],
                    'hoc_ky'  => $d['hoc_ky'],
                ],
                'ngay_tao'       => substr((string) $d['created_at'], 0, 10),
            ], $ketQua['items']),
            'total'       => $ketQua['total'],
            'page'        => $page,
            'size'        => $size,
            'total_pages' => $ketQua['total'] > 0 ? (int) ceil($ketQua['total'] / $size) : 0,
            'tu_khoa'     => $tuKhoa,
        ];
    }
}
