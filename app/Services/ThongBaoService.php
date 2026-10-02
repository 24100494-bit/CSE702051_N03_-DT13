<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\ThongBaoRepository;

/**
 * TANG NGHIEP VU (Service) - thong bao cua nguoi dung (F6.1, F6.2).
 * Moi nguoi chi thay va danh dau thong bao cua minh; thong bao cua nguoi khac tra 404 de khong lo su ton tai.
 */
class ThongBaoService
{
    public function __construct(protected ThongBaoRepository $repo)
    {
    }

    public function cuaToi(int $page, int $size, bool $chiChuaDoc, array $currentUser): array
    {
        return $this->repo->findTheoNguoiDungPhanTrang((int) $currentUser['id'], $page, $size, $chiChuaDoc);
    }

    public function danhDauDaDoc(int $id, array $currentUser): array
    {
        $thongBao = $this->repo->findById($id);

        if (!$thongBao || (int) $thongBao['nguoi_dung_id'] !== (int) $currentUser['id']) {
            throw new NotFoundException('Khong tim thay thong bao');
        }

        if ((int) $thongBao['da_doc'] === 0) {
            $this->repo->danhDauDaDoc($id);
        }

        return $this->repo->findById($id);
    }
}
