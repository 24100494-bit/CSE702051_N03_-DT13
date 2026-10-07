<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Exceptions\ForbiddenException;
use App\Repositories\NhatKyHeThongRepository;

class NhatKyHeThongService
{
    public function __construct(protected NhatKyHeThongRepository $repo)
    {
    }

    /** F5.3 - Chi thu ky khoa duoc tra cuu nhat ky he thong. */
    public function traCuu(
        int $page,
        int $size,
        ?int $nguoiDungId,
        ?string $hanhDong,
        ?string $tuKhoa,
        ?string $tuNgay,
        ?string $denNgay,
        array $currentUser
    ): array
    {
        if (!in_array('THU_KY_KHOA', array_map('strtoupper', $currentUser['danh_sach_vai_tro'] ?? []), true)) {
            throw new ForbiddenException('Ban khong co quyen tra cuu nhat ky he thong');
        }

        if ($page < 1 || $size < 1 || $size > 100) {
            throw new ValidationException([
                ['field' => 'page/size', 'issue' => 'Page phai >= 1 va size phai tu 1 den 100'],
            ]);
        }
        if ($nguoiDungId !== null && $nguoiDungId <= 0) {
            throw new ValidationException([
                ['field' => 'nguoi_dung_id', 'issue' => 'Phai la so nguyen duong'],
            ]);
        }

        foreach (['tu_ngay' => $tuNgay, 'den_ngay' => $denNgay] as $field => $value) {
            if ($value !== null && $value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new ValidationException([
                    ['field' => $field, 'issue' => 'Dinh dang phai la YYYY-MM-DD'],
                ]);
            }
        }

        if ($tuNgay !== null && $denNgay !== null && $tuNgay !== '' && $denNgay !== '' && $tuNgay > $denNgay) {
            throw new ValidationException([
                ['field' => 'tu_ngay/den_ngay', 'issue' => 'Tu ngay khong duoc lon hon den ngay'],
            ]);
        }

        return $this->repo->findAllPaginated($page, $size, $nguoiDungId, $hanhDong, $tuKhoa, $tuNgay, $denNgay);
    }
}
