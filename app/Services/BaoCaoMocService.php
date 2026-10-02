<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use CodeIgniter\HTTP\Files\UploadedFile;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - Luong 2: nop bao cao theo moc (UC03) -> GVHD duyet hoac yeu cau bo sung (UC04).
 * Moi lan nop lai la mot ban ghi moi, ban cu giu nguyen de con lich su nhan xet.
 */
class BaoCaoMocService
{
    private const DUNG_LUONG_TOI_DA = 5 * 1024 * 1024;

    /** Chu ky dau tep (magic bytes) cua cac dinh dang duoc nhan */
    private const CHU_KY_TEP = [
        'pdf'  => '%PDF-',
        'docx' => "PK\x03\x04",
    ];

    /** Bang chuyen trang thai bao cao (khung T3) */
    private const CHUYEN_TRANG_THAI = [
        'pending'            => ['duyet' => 'approved', 'yeu_cau_bo_sung' => 'revision_requested'],
        'revision_requested' => ['nop_lai' => 'pending'],
        'approved'           => [],
        'overdue'            => [],
    ];

    /** Trang thai de tai cho phep nop bao cao (de tai da duoc duyet) */
    private const DE_TAI_DUOC_NOP = ['approved', 'in_progress'];

    public function __construct(
        protected BaoCaoMocRepository $repo,
        protected DeTaiRepository $deTaiRepo,
        protected MocThoiGianRepository $mocRepo,
        protected ThanhVienNhomRepository $thanhVienRepo,
        protected NhatKyHeThongRepository $nhatKyRepo,
        protected ThongBaoRepository $thongBaoRepo,
        protected string $thuMucLuu
    ) {
    }

    /** UC03 - nop bao cao lan dau cho mot moc */
    public function nop(int $deTaiId, int $mocId, ?UploadedFile $tep, array $currentUser): array
    {
        $deTai = $this->layDeTaiCuaNhomTruong($deTaiId, $currentUser);
        $this->kiemTraMoc($deTai, $mocId);

        if ($this->repo->findMoiNhat($deTaiId, $mocId)) {
            throw new ApiException(409, 'CONFLICT', 'Moc nay da co ban nop; chi duoc nop lai khi GVHD yeu cau bo sung');
        }

        return $this->luuBanNop($deTai, $mocId, $tep, $currentUser, 'SUBMIT_REPORT', null);
    }

    /** UC03 luong thay the - nop lai khi ban moi nhat dang bi yeu cau bo sung; tao ban ghi moi */
    public function nopLai(int $baoCaoId, ?UploadedFile $tep, array $currentUser): array
    {
        $baoCao = $this->repo->findById($baoCaoId);
        if (!$baoCao) {
            throw new NotFoundException('Khong tim thay bao cao');
        }

        $deTai = $this->layDeTaiCuaNhomTruong((int) $baoCao['de_tai_id'], $currentUser);
        $mocId = (int) $baoCao['moc_thoi_gian_id'];

        $moiNhat = $this->repo->findMoiNhat((int) $deTai['id'], $mocId);
        if ((int) $moiNhat['id'] !== $baoCaoId) {
            throw new ApiException(422, 'INVALID_TRANSITION', 'Chi duoc nop lai tu ban nop moi nhat cua moc');
        }
        $this->trangThaiSau($baoCao, 'nop_lai');
        $this->kiemTraMoc($deTai, $mocId);

        return $this->luuBanNop($deTai, $mocId, $tep, $currentUser, 'RESUBMIT_REPORT', $baoCaoId);
    }

    public function xem(int $baoCaoId, array $currentUser): array
    {
        $baoCao = $this->repo->findById($baoCaoId);
        if (!$baoCao) {
            throw new NotFoundException('Khong tim thay bao cao');
        }

        $this->kiemQuyenXem($this->deTaiRepo->findById((int) $baoCao['de_tai_id']), $currentUser);

        return $this->anDuongDan($baoCao);
    }

    /** Cac ban nop cua mot de tai (moi nhat truoc), cung quyen xem voi xem() */
    public function danhSachTheoDeTai(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->deTaiRepo->findById($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $this->kiemQuyenXem($deTai, $currentUser);

        return array_map(fn (array $bc) => $this->anDuongDan($bc), $this->repo->findByDeTai($deTaiId));
    }

    /** Thu ky khoa, GVHD phu trach, thanh vien nhom duoc xem bao cao cua de tai */
    private function kiemQuyenXem(array $deTai, array $currentUser): void
    {
        $userId = (int) $currentUser['id'];

        $duocXem = $this->coVaiTro($currentUser, 'THU_KY_KHOA')
            || ($this->coVaiTro($currentUser, 'GVHD') && (int) $deTai['gvhd_id'] === $userId)
            || ($this->coVaiTro($currentUser, 'SINH_VIEN')
                && ((int) $deTai['sinh_vien_de_xuat_id'] === $userId || $this->thanhVienRepo->laThanhVien((int) $deTai['id'], $userId)));

        if (!$duocXem) {
            throw new ForbiddenException('Ban khong co quyen xem bao cao nay');
        }
    }

    /** UC04 - GVHD phu trach duyet dat */
    public function duyet(int $baoCaoId, ?string $nhanXet, array $currentUser): array
    {
        return $this->xuLyBoiGvhd($baoCaoId, 'duyet', $nhanXet, $currentUser);
    }

    /** UC04 luong thay the - yeu cau bo sung, bat buoc co nhan xet (Controller da kiem tra) */
    public function yeuCauBoSung(int $baoCaoId, string $nhanXet, array $currentUser): array
    {
        return $this->xuLyBoiGvhd($baoCaoId, 'yeu_cau_bo_sung', $nhanXet, $currentUser);
    }

    /**
     * Luu tep ra ngoai thu muc web voi ten ngau nhien, roi ghi bao_cao_moc + nhat ky + thong bao trong mot giao dich.
     * Dong de tai duoc khoa va ban nop moi nhat duoc kiem lai trong giao dich, de hai lan nop dong thoi
     * khong cung tao ban ghi. Neu ghi CSDL loi thi xoa tep vua luu de khong con tep mo coi.
     *
     * @param int|null $nopLaiTuId null = nop lan dau; co gia tri = nop lai tu ban nop nay
     */
    private function luuBanNop(array $deTai, int $mocId, ?UploadedFile $tep, array $currentUser, string $hanhDong, ?int $nopLaiTuId): array
    {
        $duoi     = $this->kiemTraTep($tep);
        $tenLuu   = bin2hex(random_bytes(16)) . '.' . $duoi;
        $tenGoc   = mb_substr($tep->getClientName(), 0, 255);
        $duongDan = $this->thuMucLuu . DIRECTORY_SEPARATOR . $tenLuu;

        $userId = (int) $currentUser['id'];

        $this->repo->transBegin();
        try {
            $this->deTaiRepo->findByIdForUpdate((int) $deTai['id']);
            $moiNhat = $this->repo->findMoiNhat((int) $deTai['id'], $mocId);

            if ($nopLaiTuId === null && $moiNhat) {
                throw new ApiException(409, 'CONFLICT', 'Moc nay da co ban nop; chi duoc nop lai khi GVHD yeu cau bo sung');
            }
            if ($nopLaiTuId !== null && (!$moiNhat || (int) $moiNhat['id'] !== $nopLaiTuId || $moiNhat['trang_thai'] !== 'revision_requested')) {
                throw new ApiException(422, 'INVALID_TRANSITION', 'Chi duoc nop lai tu ban nop moi nhat dang bi yeu cau bo sung');
            }

            if (!is_dir($this->thuMucLuu)) {
                mkdir($this->thuMucLuu, 0755, true);
            }
            $tep->move($this->thuMucLuu, $tenLuu);

            $id = $this->repo->create([
                'de_tai_id'        => (int) $deTai['id'],
                'moc_thoi_gian_id' => $mocId,
                'duong_dan_tep'    => 'bao_cao/' . $tenLuu,
                'ten_tep_goc'      => $tenGoc,
                'trang_thai'       => 'pending',
            ]);
            $this->nhatKyRepo->ghiNhan($userId, $hanhDong, 'Nop tep bao cao cho Moc ' . $mocId . ' (de tai ID: ' . $deTai['id'] . ')', $currentUser['ip'] ?? null);
            if (!empty($deTai['gvhd_id'])) {
                $this->thongBaoRepo->create([
                    'nguoi_dung_id' => (int) $deTai['gvhd_id'],
                    'tieu_de'       => 'Có báo cáo mốc chờ duyệt',
                    'noi_dung'      => 'Đề tài ID ' . $deTai['id'] . ' vừa nộp báo cáo cho mốc ' . $mocId . '.',
                ]);
            }
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            if (is_file($duongDan)) {
                unlink($duongDan);
            }
            throw $e;
        }

        return $this->anDuongDan($this->repo->findById($id));
    }

    /** Doi trang thai bao cao + ghi nhat ky + thong bao cho nhom truong trong MOT giao dich, co khoa dong */
    private function xuLyBoiGvhd(int $baoCaoId, string $hanhDong, ?string $nhanXet, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        $this->repo->transBegin();
        try {
            $baoCao = $this->repo->findByIdForUpdate($baoCaoId);
            if (!$baoCao) {
                throw new NotFoundException('Khong tim thay bao cao');
            }
            $deTai = $this->deTaiRepo->findById((int) $baoCao['de_tai_id']);
            if (!$this->coVaiTro($currentUser, 'GVHD') || (int) $deTai['gvhd_id'] !== $userId) {
                throw new ForbiddenException('Ban khong phu trach de tai cua bao cao nay');
            }

            $this->trangThaiSau($baoCao, $hanhDong);
            $laDuyet = $hanhDong === 'duyet';
            $laDuyet ? $this->repo->duyet($baoCaoId, $nhanXet) : $this->repo->yeuCauSuaLai($baoCaoId, $nhanXet);

            $this->nhatKyRepo->ghiNhan(
                $userId,
                $laDuyet ? 'APPROVE_REPORT' : 'REQUEST_REVISION',
                ($laDuyet ? 'Duyet bao cao ID: ' : 'Yeu cau bo sung bao cao ID: ') . $baoCaoId,
                $currentUser['ip'] ?? null
            );
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => (int) $deTai['sinh_vien_de_xuat_id'],
                'tieu_de'       => $laDuyet ? 'Kết quả duyệt báo cáo mốc' : 'Yêu cầu bổ sung báo cáo mốc',
                'noi_dung'      => ($laDuyet ? 'Báo cáo mốc của nhóm đã được duyệt.' : 'Báo cáo mốc của nhóm cần bổ sung.')
                    . ($nhanXet ? ' Nhận xét: ' . $nhanXet : ''),
            ]);

            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->anDuongDan($this->repo->findById($baoCaoId));
    }

    /** De tai ton tai, da duoc duyet, va nguoi goi la nhom truong (nguoi de xuat) */
    private function layDeTaiCuaNhomTruong(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->deTaiRepo->findById($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }
        if (!$this->coVaiTro($currentUser, 'SINH_VIEN') || (int) $deTai['sinh_vien_de_xuat_id'] !== (int) $currentUser['id']) {
            throw new ForbiddenException('Chi nhom truong cua de tai moi duoc nop bao cao');
        }
        if (!in_array($deTai['trang_thai'], self::DE_TAI_DUOC_NOP, true)) {
            throw new ApiException(422, 'INVALID_TRANSITION', 'De tai chua duoc duyet, chua nop bao cao duoc');
        }

        return $deTai;
    }

    /** Moc phai thuoc dung lop cua de tai va con han nop */
    private function kiemTraMoc(array $deTai, int $mocId): void
    {
        $moc = $this->mocRepo->findById($mocId);
        if (!$moc || (int) $moc['lop_hoc_phan_id'] !== (int) $deTai['lop_hoc_phan_id']) {
            throw new ValidationException([['field' => 'moc_thoi_gian_id', 'issue' => 'Moc khong thuoc lop cua de tai']]);
        }
        if (strtotime($moc['han_nop']) < time()) {
            throw new ApiException(422, 'MOC_DA_DONG', 'Moc da het han nop');
        }
    }

    /** Kiem tra co tep, dung luong, duoi tep va chu ky tep thuc; tra ve duoi tep */
    private function kiemTraTep(?UploadedFile $tep): string
    {
        if ($tep !== null && in_array($tep->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new ValidationException([['field' => 'tep', 'issue' => 'Dung luong toi da 5 MB']]);
        }
        if ($tep === null || !$tep->isValid()) {
            throw new ValidationException([['field' => 'tep', 'issue' => 'Bat buoc gui tep bao cao']]);
        }
        if ($tep->getSize() > self::DUNG_LUONG_TOI_DA) {
            throw new ValidationException([['field' => 'tep', 'issue' => 'Dung luong toi da 5 MB']]);
        }

        $duoi = strtolower($tep->getClientExtension());
        if (!isset(self::CHU_KY_TEP[$duoi])) {
            throw new ValidationException([['field' => 'tep', 'issue' => 'Chi nhan tep .pdf hoac .docx']]);
        }

        $dauTep = (string) file_get_contents($tep->getTempName(), false, null, 0, 8);
        if (!str_starts_with($dauTep, self::CHU_KY_TEP[$duoi])) {
            throw new ValidationException([['field' => 'tep', 'issue' => 'Noi dung tep khong dung dinh dang ' . $duoi]]);
        }

        return $duoi;
    }

    private function trangThaiSau(array $baoCao, string $hanhDong): string
    {
        $hienTai = $baoCao['trang_thai'];
        $cho     = self::CHUYEN_TRANG_THAI[$hienTai] ?? [];

        if (!isset($cho[$hanhDong])) {
            throw new ApiException(422, 'INVALID_TRANSITION', 'Khong the ' . $hanhDong . ' bao cao o trang thai ' . $hienTai);
        }

        return $cho[$hanhDong];
    }

    /** Khong tra duong dan luu tep noi bo cho client */
    private function anDuongDan(array $baoCao): array
    {
        unset($baoCao['duong_dan_tep']);

        return $baoCao;
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
