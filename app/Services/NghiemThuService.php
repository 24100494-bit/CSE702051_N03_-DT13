<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\DiemNghiemThuRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - Luong 3: cham diem nghiem thu (UC06)
 * de tai completed (du dieu kien) -> hoi dong cham (moi nguoi mot lan, 0-10)
 * -> thu ky khoa chot -> accepted (da nghiem thu), diem cuoi = trung binh cac luot cham.
 */
class NghiemThuService
{
    public const DIEM_TOI_THIEU = 0;
    public const DIEM_TOI_DA    = 10;

    private DeTaiRepository $deTaiRepo;
    private DiemNghiemThuRepository $diemRepo;
    private ThanhVienNhomRepository $thanhVienRepo;
    private BaoCaoMocRepository $baoCaoRepo;
    private MocThoiGianRepository $mocRepo;
    private NguoiDungRepository $nguoiDungRepo;
    private NhatKyHeThongRepository $nhatKyRepo;
    private ThongBaoRepository $thongBaoRepo;
    private TrangThaiWorkflow $workflow;

    public function __construct(
        DeTaiRepository $deTaiRepo,
        DiemNghiemThuRepository $diemRepo,
        ThanhVienNhomRepository $thanhVienRepo,
        BaoCaoMocRepository $baoCaoRepo,
        MocThoiGianRepository $mocRepo,
        NguoiDungRepository $nguoiDungRepo,
        NhatKyHeThongRepository $nhatKyRepo,
        ThongBaoRepository $thongBaoRepo
    ) {
        $this->deTaiRepo     = $deTaiRepo;
        $this->diemRepo      = $diemRepo;
        $this->thanhVienRepo = $thanhVienRepo;
        $this->baoCaoRepo    = $baoCaoRepo;
        $this->mocRepo       = $mocRepo;
        $this->nguoiDungRepo = $nguoiDungRepo;
        $this->nhatKyRepo    = $nhatKyRepo;
        $this->thongBaoRepo  = $thongBaoRepo;
        $this->workflow      = new TrangThaiWorkflow(DeTaiService::CHUYEN_TRANG_THAI);
    }

    /** F4.1 - danh sach ho so du dieu kien / da nghiem thu, kem diem trung binh va viec toi da cham chua */
    public function danhSachHoSo(int $page, int $size, ?string $trangThai, array $currentUser): array
    {
        $ketQua = $this->deTaiRepo->findTheoPhamVi($page, $size, $trangThai, null, null, DeTaiService::TRANG_THAI_HOI_DONG);
        $userId = (int) $currentUser['id'];

        foreach ($ketQua['items'] as &$deTai) {
            $deTai += $this->diemRepo->thongKe((int) $deTai['id']);
            $deTai['toi_da_cham'] = $this->diemRepo->daCham((int) $deTai['id'], $userId);
        }
        unset($deTai);

        return $ketQua;
    }

    /** F4.2 - ho so nghiem thu: de tai, GVHD, nhom, ban nop moi nhat cua tung moc kem nhan xet GVHD, diem da cham */
    public function hoSo(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->layDeTaiChoHoiDong($deTaiId, $currentUser);

        $gvhd = $deTai['gvhd_id'] ? $this->nguoiDungRepo->findById((int) $deTai['gvhd_id']) : null;

        $banNop = [];
        foreach ($this->mocRepo->findByLopHocPhan((int) $deTai['lop_hoc_phan_id']) as $moc) {
            $moiNhat = $this->baoCaoRepo->findMoiNhat($deTaiId, (int) $moc['id']);
            $banNop[] = [
                'moc_thoi_gian_id' => (int) $moc['id'],
                'ten_moc'          => $moc['ten_moc'],
                'bat_buoc'         => (int) $moc['bat_buoc'],
                'ban_nop_moi_nhat' => $moiNhat ? [
                    'id'          => (int) $moiNhat['id'],
                    'ten_tep_goc' => $moiNhat['ten_tep_goc'],
                    'trang_thai'  => $moiNhat['trang_thai'],
                    'nhan_xet_gv' => $moiNhat['nhan_xet_gv'],
                    'created_at'  => $moiNhat['created_at'],
                ] : null,
            ];
        }

        return [
            'de_tai'      => $deTai,
            'gvhd'        => $gvhd ? ['id' => (int) $gvhd['id'], 'ho_ten' => $gvhd['ho_ten'], 'email' => $gvhd['email']] : null,
            'thanh_vien'  => $this->thanhVienRepo->findByDeTai($deTaiId),
            'bao_cao_moc' => $banNop,
            'diem'        => $this->diemRepo->findByDeTaiKemNguoiCham($deTaiId),
            'toi_da_cham' => $this->diemRepo->daCham($deTaiId, (int) $currentUser['id']),
        ] + $this->diemRepo->thongKe($deTaiId);
    }

    /**
     * F4.3 - thanh vien hoi dong cham diem. Dong de tai bi khoa (FOR UPDATE) nen cham va chot
     * khong chen nhau: da chot thi khong cham them, hai lan cham cua cung mot nguoi chi lot mot lan.
     */
    public function cham(int $deTaiId, float $diem, ?string $nhanXet, array $currentUser): array
    {
        if ($diem < self::DIEM_TOI_THIEU || $diem > self::DIEM_TOI_DA) {
            throw new ApiException(422, 'DIEM_NGOAI_KHOANG', 'Diem phai nam trong khoang 0 - 10', [['field' => 'diem', 'issue' => 'Ngoai khoang 0 - 10']]);
        }

        $userId = (int) $currentUser['id'];

        $this->deTaiRepo->transBegin();
        try {
            $deTai = $this->deTaiRepo->findByIdForUpdate($deTaiId);
            if (!$deTai) {
                throw new NotFoundException('Khong tim thay de tai');
            }
            if (!$this->coVaiTro($currentUser, 'HOI_DONG')) {
                throw new ForbiddenException('Chi thanh vien hoi dong moi duoc cham diem');
            }
            if ($deTai['trang_thai'] !== 'completed') {
                throw new ApiException(422, 'INVALID_TRANSITION', $deTai['trang_thai'] === 'accepted'
                    ? 'De tai da chot nghiem thu, khong cham them'
                    : 'De tai chua du dieu kien nghiem thu');
            }
            if ($this->diemRepo->daCham($deTaiId, $userId)) {
                throw new ApiException(409, 'CONFLICT', 'Ban da cham de tai nay');
            }

            $id = $this->diemRepo->create([
                'de_tai_id'   => $deTaiId,
                'hoi_dong_id' => $userId,
                'diem'        => round($diem, 2),
                'nhan_xet'    => $nhanXet,
            ]);
            $this->nhatKyRepo->ghiNhan($userId, 'GRADE_TOPIC', 'Cham nghiem thu de tai ID: ' . $deTaiId . ' - diem ' . round($diem, 2), $currentUser['ip'] ?? null);

            $this->deTaiRepo->transCommit();
        } catch (DatabaseException $e) {
            $this->deTaiRepo->transRollback();
            // Lop chan cuoi: UNIQUE (de_tai_id, hoi_dong_id) bat trung khi Service bi bo qua
            if (str_contains($e->getMessage(), 'uq_diem_detai_hoidong') || str_contains($e->getMessage(), 'Duplicate')) {
                throw new ApiException(409, 'CONFLICT', 'Ban da cham de tai nay');
            }
            throw $e;
        } catch (Throwable $e) {
            $this->deTaiRepo->transRollback();
            throw $e;
        }

        return ['id' => $id, 'de_tai_id' => $deTaiId, 'diem' => round($diem, 2), 'nhan_xet' => $nhanXet]
            + $this->diemRepo->thongKe($deTaiId);
    }

    /**
     * F4.4 - xem diem. Hoi dong, thu ky xem bat cu luc nao; sinh vien trong nhom va GVHD phu trach
     * chi thay diem sau khi thu ky chot (cong_bo = true).
     */
    public function xemDiem(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->deTaiRepo->findById($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $userId  = (int) $currentUser['id'];
        $noiBo   = $this->coVaiTro($currentUser, 'THU_KY_KHOA')
            || ($this->coVaiTro($currentUser, 'HOI_DONG') && in_array($deTai['trang_thai'], DeTaiService::TRANG_THAI_HOI_DONG, true));
        $nhom    = $this->coVaiTro($currentUser, 'SINH_VIEN')
            && ((int) $deTai['sinh_vien_de_xuat_id'] === $userId || $this->thanhVienRepo->laThanhVien($deTaiId, $userId));
        $gvhd    = $this->coVaiTro($currentUser, 'GVHD') && (int) $deTai['gvhd_id'] === $userId;

        if (!$noiBo && !$nhom && !$gvhd) {
            throw new ForbiddenException('Ban khong co quyen xem diem de tai nay');
        }

        $congBo = $deTai['trang_thai'] === 'accepted';
        if (!$noiBo && !$congBo) {
            return ['de_tai_id' => $deTaiId, 'trang_thai' => $deTai['trang_thai'], 'cong_bo' => false,
                'so_luot_cham' => null, 'diem_trung_binh' => null, 'chi_tiet' => []];
        }

        return ['de_tai_id' => $deTaiId, 'trang_thai' => $deTai['trang_thai'], 'cong_bo' => $congBo]
            + $this->diemRepo->thongKe($deTaiId)
            + ['chi_tiet' => $this->diemRepo->findByDeTaiKemNguoiCham($deTaiId)];
    }

    /** F4.5 - thu ky khoa chot ket qua: completed -> accepted, can it nhat mot luot cham */
    public function chot(int $deTaiId, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        $this->deTaiRepo->transBegin();
        try {
            $deTai = $this->deTaiRepo->findByIdForUpdate($deTaiId);
            if (!$deTai) {
                throw new NotFoundException('Khong tim thay de tai');
            }
            if (!$this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
                throw new ForbiddenException('Chi thu ky khoa moi duoc chot nghiem thu');
            }

            $moi      = $this->workflow->next((string) $deTai['trang_thai'], 'chot_nghiem_thu');
            $thongKe  = $this->diemRepo->thongKe($deTaiId);
            if ($thongKe['so_luot_cham'] < 1) {
                throw new ApiException(422, 'CHUA_CO_DIEM', 'Hoi dong chua cham, chua chot duoc');
            }

            $this->deTaiRepo->capNhatTrangThai($deTaiId, $moi);
            $this->nhatKyRepo->ghiNhan(
                $userId,
                'ACCEPT_TOPIC',
                'Chot nghiem thu de tai ID: ' . $deTaiId . ' - diem TB ' . $thongKe['diem_trung_binh'] . ' (' . $thongKe['so_luot_cham'] . ' luot cham)',
                $currentUser['ip'] ?? null
            );

            $noiDung = 'Đề tài ID ' . $deTaiId . ' đã được nghiệm thu. Điểm trung bình: ' . $thongKe['diem_trung_binh'] . '.';
            foreach (array_unique(array_filter([(int) $deTai['sinh_vien_de_xuat_id'], (int) $deTai['gvhd_id']])) as $nguoiNhan) {
                $this->thongBaoRepo->create([
                    'nguoi_dung_id' => $nguoiNhan,
                    'tieu_de'       => 'Kết quả nghiệm thu',
                    'noi_dung'      => $noiDung,
                ]);
            }

            $this->deTaiRepo->transCommit();
        } catch (Throwable $e) {
            $this->deTaiRepo->transRollback();
            throw $e;
        }

        return ['de_tai' => $this->deTaiRepo->findById($deTaiId)] + $thongKe;
    }

    /** De tai ton tai va o trang thai hoi dong duoc xem (completed / accepted) */
    private function layDeTaiChoHoiDong(int $deTaiId, array $currentUser): array
    {
        $deTai = $this->deTaiRepo->findById($deTaiId);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }
        if (!$this->coVaiTro($currentUser, 'HOI_DONG') || !in_array($deTai['trang_thai'], DeTaiService::TRANG_THAI_HOI_DONG, true)) {
            throw new ForbiddenException('Ho so chua du dieu kien nghiem thu');
        }

        return $deTai;
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
