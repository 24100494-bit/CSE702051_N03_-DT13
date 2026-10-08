<?php

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;

/**
 * TANG NGHIEP VU (Service) - tai tep bao cao moc ve co kiem quyen (khoi K6, F3.11).
 * Tep luu ngoai thu muc web (writable/uploads) nen chi lay duoc qua ham nay.
 * Duoc tai: thu ky khoa, GVHD phu trach, sinh vien trong nhom; hoi dong khi de tai du dieu kien / da nghiem thu.
 */
class TaiTepService
{
    /** Duong dan luu hop le: dung dang do luuBanNop tao ra (ten ngau nhien 32 ky tu hex) */
    private const MAU_DUONG_DAN = '#^bao_cao/[0-9a-f]{32}\.(pdf|docx)$#';

    private const LOAI_NOI_DUNG = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(
        private BaoCaoMocRepository $baoCaoRepo,
        private DeTaiRepository $deTaiRepo,
        private ThanhVienNhomRepository $thanhVienRepo,
        private NhatKyHeThongRepository $nhatKyRepo,
        private string $thuMucGoc
    ) {
    }

    /**
     * Tra ve [duong dan tuyet doi, ten tai ve, loai noi dung].
     * Khong co quyen: 403 va ghi UNAUTHORIZED_ACCESS; ban ghi hoac tep khong con: 404.
     */
    public function baoCaoMoc(int $baoCaoId, array $currentUser): array
    {
        $baoCao = $this->baoCaoRepo->findById($baoCaoId);
        if (!$baoCao) {
            throw new NotFoundException('Khong tim thay bao cao');
        }
        $deTai = $this->deTaiRepo->findById((int) $baoCao['de_tai_id']);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $userId = (int) $currentUser['id'];
        if (!$this->duocTai($deTai, $currentUser)) {
            $this->nhatKyRepo->ghiNhan($userId, 'UNAUTHORIZED_ACCESS', 'Thu tai tep bao cao ID: ' . $baoCaoId . ' khi khong co quyen', $currentUser['ip'] ?? null);
            throw new ForbiddenException('Ban khong co quyen tai tep bao cao nay');
        }

        // Chi mo tep nam dung thu muc luu, ten dung mau; chan moi duong dan la (../, duong dan tuyet doi)
        $tuongDoi = (string) $baoCao['duong_dan_tep'];
        $goc      = realpath($this->thuMucGoc);
        $tep      = realpath($this->thuMucGoc . DIRECTORY_SEPARATOR . $tuongDoi);
        if (!preg_match(self::MAU_DUONG_DAN, $tuongDoi) || $goc === false || $tep === false
            || !str_starts_with($tep, $goc . DIRECTORY_SEPARATOR) || !is_file($tep)) {
            throw new NotFoundException('Tep bao cao khong con tren may chu');
        }

        $duoi = strtolower(pathinfo($tep, PATHINFO_EXTENSION));
        $this->nhatKyRepo->ghiNhan($userId, 'DOWNLOAD_REPORT', 'Tai tep bao cao ID: ' . $baoCaoId . ' (de tai ID: ' . $deTai['id'] . ')', $currentUser['ip'] ?? null);

        return [$tep, $this->tenTaiVe((string) $baoCao['ten_tep_goc'], $duoi), self::LOAI_NOI_DUNG[$duoi]];
    }

    private function duocTai(array $deTai, array $currentUser): bool
    {
        $userId = (int) $currentUser['id'];

        return $this->coVaiTro($currentUser, 'THU_KY_KHOA')
            || ($this->coVaiTro($currentUser, 'GVHD') && (int) $deTai['gvhd_id'] === $userId)
            || ($this->coVaiTro($currentUser, 'SINH_VIEN')
                && ((int) $deTai['sinh_vien_de_xuat_id'] === $userId || $this->thanhVienRepo->laThanhVien((int) $deTai['id'], $userId)))
            || ($this->coVaiTro($currentUser, 'HOI_DONG') && in_array($deTai['trang_thai'], DeTaiService::TRANG_THAI_HOI_DONG, true));
    }

    /** Ten goc bo ky tu dieu khien, ngoac kep, dau gach; dam bao dung duoi tep that */
    private function tenTaiVe(string $tenGoc, string $duoi): string
    {
        $ten = trim(preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/u', '_', basename($tenGoc)) ?? '');
        if ($ten === '' || strtolower(pathinfo($ten, PATHINFO_EXTENSION)) !== $duoi) {
            $ten = ($ten === '' ? 'bao-cao' : $ten) . '.' . $duoi;
        }

        return $ten;
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
