<?php

namespace App\Services;

use App\Repositories\DeTaiRepository;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;

/**
 * TANG NGHIEP VU (Service)
 * Noi thuc hien: quy tac nghiep vu, kiem tra quyen tren dung doi tuong (chong IDOR),
 * mo/dong giao dich khi can.
 * KHONG duoc: doc truc tiep $_GET/$_POST hoac tra ve ma HTTP - do la viec cua Controller.
 */
class DeTaiService
{
    protected DeTaiRepository $repo;

    public function __construct(DeTaiRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param array $currentUser vi du: ['id' => 5, 'vai_tro' => 'sinh_vien']
     */
    public function getForUser(int $id, array $currentUser): array
    {
        $deTai = $this->repo->findById($id);

        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $isOwner = (int) $deTai['nguoi_tao_id'] === (int) $currentUser['id'];
        $isGvhdPhuTrach = $currentUser['vai_tro'] === 'gvhd' && (int) $deTai['gvhd_id'] === (int) $currentUser['id'];
        $isThuKy = $currentUser['vai_tro'] === 'thu_ky_khoa';
        $isHoiDong = $currentUser['vai_tro'] === 'hoi_dong';

        // Muc 2 - quyen tren doi tuong: chi 4 truong hop tren moi duoc xem
        if (!$isOwner && !$isGvhdPhuTrach && !$isThuKy && !$isHoiDong) {
            throw new ForbiddenException('Ban khong co quyen xem de tai nay');
        }

        return $deTai;
    }

    public function listForUser(int $page, int $size, ?string $trangThai, array $currentUser): array
    {
        // Sinh vien chi thay de tai cua chinh minh -- vi du don gian ve loc theo pham vi
        if ($currentUser['vai_tro'] === 'sinh_vien') {
            // trong thuc te se can them dieu kien nguoi_tao_id vao repository
        }

        return $this->repo->findAllPaginated($page, $size, $trangThai);
    }
}
