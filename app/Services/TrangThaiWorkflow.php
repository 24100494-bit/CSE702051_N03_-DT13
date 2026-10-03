<?php

namespace App\Services;

use App\Exceptions\ApiException;

/**
 * May trang thai dung chung cho cac luong nghiep vu.
 * Service van la noi kiem tra quyen; lop nay chi kiem tra chuyen trang thai.
 */
class TrangThaiWorkflow
{
    public function __construct(private array $chuyenTrangThai)
    {
    }

    public function next(string $trangThaiHienTai, string $hanhDong): ?string
    {
        $cacChuyen = $this->chuyenTrangThai[$trangThaiHienTai] ?? [];

        if (!array_key_exists($hanhDong, $cacChuyen)) {
            throw new ApiException(
                422,
                'INVALID_TRANSITION',
                'Khong the ' . $hanhDong . ' o trang thai ' . $trangThaiHienTai
            );
        }

        return $cacChuyen[$hanhDong];
    }

    public function actions(string $trangThaiHienTai): array
    {
        return array_keys($this->chuyenTrangThai[$trangThaiHienTai] ?? []);
    }
}
