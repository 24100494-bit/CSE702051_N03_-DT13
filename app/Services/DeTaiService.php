<?php

namespace App\Services;

use App\Repositories\DeTaiRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use Throwable;

/**
 * TANG NGHIEP VU (Service)
 * Noi thuc hien: quy tac nghiep vu, kiem tra quyen tren dung doi tuong (chong IDOR),
 * mo/dong giao dich khi can.
 * KHONG duoc: doc truc tiep $_GET/$_POST hoac tra ve ma HTTP - do la viec cua Controller.
 *
 * Luong 1: sinh vien de xuat de tai (UC01) -> GVHD phu trach duyet hoac tu choi (UC02).
 */
class DeTaiService
{
    /**
     * Bang chuyen trang thai (khung T3): trang thai hien tai -> hanh dong -> trang thai moi.
     * Hanh dong khong co trong bang thi bi tu choi voi 422.
     */
    private const CHUYEN_TRANG_THAI = [
        'pending'     => ['duyet' => 'approved', 'tu_choi' => 'rejected', 'sua' => 'pending', 'xoa' => null],
        'rejected'    => ['sua' => 'pending', 'xoa' => null],
        'approved'    => [],
        'in_progress' => [],
        'completed'   => [],
    ];

    protected DeTaiRepository $repo;
    protected ?ThanhVienNhomRepository $thanhVienRepo;
    protected ?NhatKyHeThongRepository $nhatKyRepo;
    protected ?ThongBaoRepository $thongBaoRepo;
    protected TrangThaiWorkflow $workflow;

    public function __construct(
        DeTaiRepository $repo,
        ?ThanhVienNhomRepository $thanhVienRepo = null,
        ?NhatKyHeThongRepository $nhatKyRepo = null,
        ?ThongBaoRepository $thongBaoRepo = null
    ) {
        $this->repo          = $repo;
        $this->thanhVienRepo = $thanhVienRepo;
        $this->nhatKyRepo    = $nhatKyRepo;
        $this->thongBaoRepo  = $thongBaoRepo;
        $this->workflow      = new TrangThaiWorkflow(self::CHUYEN_TRANG_THAI);
    }

    /**
     * @param array $currentUser vi du: ['id' => 5, 'vai_tro' => 'sinh_vien', 'danh_sach_vai_tro' => [...]]
     */
    public function getForUser(int $id, array $currentUser): array
    {
        $deTai = $this->repo->findById($id);

        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }

        $userId = (int) ($currentUser['id'] ?? 0);

        // Sinh vien: de tai do chinh minh de xuat hoac minh la thanh vien nhom
        $isThanhVien = $this->coVaiTro($currentUser, 'SINH_VIEN')
            && ((int) $deTai['sinh_vien_de_xuat_id'] === $userId
                || ($this->thanhVienRepo && $this->thanhVienRepo->laThanhVien($id, $userId)));

        // GVHD: chi duoc xem de tai do minh phu trach
        $isGvhdPhuTrach = $this->coVaiTro($currentUser, 'GVHD')
            && (int) $deTai['gvhd_id'] === $userId;

        // Thu ky khoa: duoc xem moi de tai
        $isThuKy = $this->coVaiTro($currentUser, 'THU_KY_KHOA');

        // Hoi dong: chi de tai da hoan thanh (du dieu kien nghiem thu)
        $isHoiDong = $this->coVaiTro($currentUser, 'HOI_DONG') && $deTai['trang_thai'] === 'completed';

        // Kiem quyen tren dung doi tuong (chong IDOR)
        if (!$isThanhVien && !$isGvhdPhuTrach && !$isThuKy && !$isHoiDong) {
            throw new ForbiddenException('Ban khong co quyen xem de tai nay');
        }

        return $deTai;
    }

    public function listForUser(int $page, int $size, ?string $trangThai, array $currentUser): array
    {
        $userId = (int) ($currentUser['id'] ?? 0);

        if ($this->coVaiTro($currentUser, 'THU_KY_KHOA')) {
            return $this->repo->findTheoPhamVi($page, $size, $trangThai);
        }
        if ($this->coVaiTro($currentUser, 'GVHD')) {
            return $this->repo->findTheoPhamVi($page, $size, $trangThai, null, $userId);
        }
        if ($this->coVaiTro($currentUser, 'SINH_VIEN')) {
            return $this->repo->findTheoPhamVi($page, $size, $trangThai, $userId);
        }
        if ($this->coVaiTro($currentUser, 'HOI_DONG')) {
            return $this->repo->findTheoPhamVi($page, $size, $trangThai, null, null, ['completed']);
        }

        return ['items' => [], 'total' => 0, 'page' => $page, 'size' => $size];
    }

    /**
     * UC01 - de xuat de tai. Ghi 3 bang trong mot giao dich: de_tai, thanh_vien_nhom (nhom truong), nhat_ky_he_thong.
     * $input da duoc Controller kiem tra dinh dang: ten_de_tai, mo_ta_pham_vi, lop_hoc_phan_id
     */
    public function deXuat(array $input, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        $this->repo->transBegin();
        try {
            // Khoa dong sinh vien va dong lop truoc khi kiem tra, de hai de xuat dong thoi khong cung lot qua
            if (!$this->repo->khoaTruocKhiDeXuat($userId, (int) $input['lop_hoc_phan_id'])) {
                throw new ValidationException([['field' => 'lop_hoc_phan_id', 'issue' => 'Lop hoc phan khong ton tai']]);
            }
            if ($this->repo->coDeTaiDangHoatDong($userId)) {
                throw new ApiException(422, 'DA_CO_DE_TAI', 'Sinh vien dang co de tai chua bi tu choi, khong de xuat them');
            }
            if ($this->repo->existsTenTrongLop($input['lop_hoc_phan_id'], $input['ten_de_tai'])) {
                throw new ValidationException([['field' => 'ten_de_tai', 'issue' => 'Trung ten de tai da co trong lop, vui long doi ten']]);
            }

            $id = $this->repo->create([
                'ten_de_tai'           => $input['ten_de_tai'],
                'mo_ta_pham_vi'        => $input['mo_ta_pham_vi'],
                'lop_hoc_phan_id'      => $input['lop_hoc_phan_id'],
                'sinh_vien_de_xuat_id' => $userId,
                'trang_thai'           => 'pending',
            ]);
            $this->thanhVienRepo->themThanhVien($id, $userId, 'leader');
            $this->nhatKyRepo->ghiNhan($userId, 'PROPOSE_TOPIC', 'De xuat de tai ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    /** YCCN-12 - lich su chuyen trang thai cua de tai. */
    public function lichSu(int $id, array $currentUser): array
    {
        $deTai = $this->getForUser($id, $currentUser);
        $lichSu = $this->nhatKyRepo->findLichSuDeTai($id);
        $trangThaiDau = 'pending';
        $ketQua = [];

        foreach ($lichSu as $suKien) {
            $hanhDong = $suKien['hanh_dong'];
            $trangThaiMoi = match ($hanhDong) {
                'PROPOSE_TOPIC' => 'pending',
                'APPROVE_TOPIC' => 'approved',
                'REJECT_TOPIC' => 'rejected',
                default => $trangThaiDau,
            };

            $ketQua[] = [
                'hanh_dong' => $hanhDong,
                'tu_trang_thai' => $trangThaiDau,
                'den_trang_thai' => $trangThaiMoi,
                'nguoi_dung_id' => $suKien['nguoi_dung_id'],
                'thoi_gian' => $suKien['created_at'],
                'chi_tiet' => $suKien['chi_tiet'],
            ];
            $trangThaiDau = $trangThaiMoi;
        }

        return [
            'de_tai_id' => (int) $deTai['id'],
            'trang_thai_hien_tai' => $deTai['trang_thai'],
            'lich_su' => $ketQua,
        ];
    }

    /** Sua ten, mo ta khi de tai con pending hoac bi tu choi; de tai bi tu choi sau khi sua quay ve pending (UC02 luong thay the) */
    public function capNhat(int $id, array $input, array $currentUser): array
    {
        $deTai = $this->layDeTaiCuaChu($id, $currentUser);
        $moi   = $this->trangThaiSau($deTai, 'sua');

        if (isset($input['ten_de_tai'])
            && $this->repo->existsTenTrongLop((int) $deTai['lop_hoc_phan_id'], $input['ten_de_tai'], $id)) {
            throw new ValidationException([['field' => 'ten_de_tai', 'issue' => 'Trung ten de tai da co trong lop, vui long doi ten']]);
        }

        $this->repo->capNhat($id, array_merge($input, ['trang_thai' => $moi]));

        return $this->repo->findById($id);
    }

    /** Xoa de xuat chua duyet (pending hoac rejected) */
    public function xoa(int $id, array $currentUser): void
    {
        $deTai = $this->layDeTaiCuaChu($id, $currentUser);
        $this->trangThaiSau($deTai, 'xoa');

        $this->repo->transBegin();
        try {
            $this->repo->delete($id);
            $this->nhatKyRepo->ghiNhan((int) $currentUser['id'], 'DELETE_TOPIC', 'Xoa de xuat de tai ID: ' . $id, $currentUser['ip'] ?? null);
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }
    }

    /** UC02 - GVHD phu trach duyet de tai */
    public function duyet(int $id, ?string $nhanXet, array $currentUser): array
    {
        return $this->chuyenTrangThaiBoiGvhd($id, 'duyet', $nhanXet, $currentUser);
    }

    /** UC02 - GVHD phu trach tu choi, bat buoc co ly do (Controller da kiem tra khong rong) */
    public function tuChoi(int $id, string $lyDo, array $currentUser): array
    {
        return $this->chuyenTrangThaiBoiGvhd($id, 'tu_choi', $lyDo, $currentUser);
    }

    /**
     * Doi trang thai + ghi nhat ky + thong bao cho nhom truong trong MOT giao dich.
     * Dong de tai duoc khoa (SELECT ... FOR UPDATE) de hai yeu cau dong thoi khong cung chuyen trang thai.
     */
    private function chuyenTrangThaiBoiGvhd(int $id, string $hanhDong, ?string $ghiChu, array $currentUser): array
    {
        $userId = (int) $currentUser['id'];

        $this->repo->transBegin();
        try {
            $deTai = $this->repo->findByIdForUpdate($id);
            if (!$deTai) {
                throw new NotFoundException('Khong tim thay de tai');
            }
            if (!$this->coVaiTro($currentUser, 'GVHD') || (int) $deTai['gvhd_id'] !== $userId) {
                $this->nhatKyRepo->ghiNhan($userId, 'UNAUTHORIZED_ACCESS', 'Thu ' . $hanhDong . ' de tai ID: ' . $id . ' khi khong phu trach', $currentUser['ip'] ?? null);
                $this->repo->transCommit();
                throw new ForbiddenException('Ban khong phu trach de tai nay');
            }

            $moi = $this->trangThaiSau($deTai, $hanhDong);
            $this->repo->capNhatTrangThai($id, $moi);

            $laDuyet = $hanhDong === 'duyet';
            $this->nhatKyRepo->ghiNhan(
                $userId,
                $laDuyet ? 'APPROVE_TOPIC' : 'REJECT_TOPIC',
                ($laDuyet ? 'Duyet de tai ID: ' : 'Tu choi de tai ID: ') . $id . ($ghiChu ? ' - ' . $ghiChu : ''),
                $currentUser['ip'] ?? null
            );
            $this->thongBaoRepo->create([
                'nguoi_dung_id' => (int) $deTai['sinh_vien_de_xuat_id'],
                'tieu_de'       => 'Kết quả duyệt đề tài',
                'noi_dung'      => $laDuyet
                    ? 'Đề tài ID ' . $id . ' của nhóm đã được phê duyệt.' . ($ghiChu ? ' Nhận xét: ' . $ghiChu : '')
                    : 'Đề tài ID ' . $id . ' của nhóm chưa được chấp thuận. Lý do: ' . $ghiChu,
            ]);

            $this->repo->transCommit();
        } catch (ForbiddenException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->repo->findById($id);
    }

    /** De tai ton tai va nguoi goi la sinh vien de xuat (chu de tai) */
    private function layDeTaiCuaChu(int $id, array $currentUser): array
    {
        $deTai = $this->repo->findById($id);
        if (!$deTai) {
            throw new NotFoundException('Khong tim thay de tai');
        }
        if (!$this->coVaiTro($currentUser, 'SINH_VIEN') || (int) $deTai['sinh_vien_de_xuat_id'] !== (int) $currentUser['id']) {
            throw new ForbiddenException('Chi sinh vien de xuat moi duoc thao tac tren de tai nay');
        }

        return $deTai;
    }

    /** Tra ve trang thai moi theo may trang thai T3, hoac 422 neu hanh dong khong hop le. */
    private function trangThaiSau(array $deTai, string $hanhDong): ?string
    {
        return $this->workflow->next((string) $deTai['trang_thai'], $hanhDong);
    }

    private function coVaiTro(array $currentUser, string $vaiTro): bool
    {
        $ds = $currentUser['danh_sach_vai_tro'] ?? [$currentUser['vai_tro'] ?? ''];

        return in_array($vaiTro, array_map(static fn ($v) => strtoupper(trim((string) $v)), $ds), true);
    }
}
