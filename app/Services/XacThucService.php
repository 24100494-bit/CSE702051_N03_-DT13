<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\UnauthenticatedException;
use App\Exceptions\ValidationException;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - dang ky, dang nhap
 * Mat khau bam bang bcrypt cost 12 qua ham co san password_hash (Muc 5.3 tai lieu ky thuat).
 * Dang nhap thanh cong/that bai, dang xuat, doi mat khau duoc ghi nhat ky he thong (khoi K8); khong bao gio ghi mat khau.
 */
class XacThucService
{
    public const BCRYPT_COST = 12;

    /**
     * Ma bam gia de van chay password_verify khi ten dang nhap khong ton tai,
     * tranh do thoi gian phan hoi ma biet duoc tai khoan nao co that.
     */
    private const MA_BAM_GIA = '$2y$12$f6PVy9lTz7QHHDcDcnvroO31dlX.03i0lD16gD4fdhg3MAEy2RX3m';

    protected NguoiDungRepository $repo;
    protected ?NhatKyHeThongRepository $nhatKyRepo;

    public function __construct(NguoiDungRepository $repo, ?NhatKyHeThongRepository $nhatKyRepo = null)
    {
        $this->repo       = $repo;
        $this->nhatKyRepo = $nhatKyRepo;
    }

    /**
     * Dang ky chi tao tai khoan SINH_VIEN; truong vai tro gui len (neu co) bi bo qua.
     */
    public function dangKy(array $input): array
    {
        $trung = [];
        if ($this->repo->existsTenDangNhap($input['ten_dang_nhap'])) {
            $trung[] = ['field' => 'ten_dang_nhap', 'issue' => 'Ten dang nhap da duoc su dung'];
        }
        if ($this->repo->existsEmail($input['email'])) {
            $trung[] = ['field' => 'email', 'issue' => 'Email da duoc su dung'];
        }
        if ($trung) {
            throw new ValidationException($trung);
        }

        $this->repo->transBegin();
        try {
            $id = $this->repo->create([
                'ten_dang_nhap' => $input['ten_dang_nhap'],
                'mat_khau_hash' => password_hash($input['mat_khau'], PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]),
                'ho_ten'        => $input['ho_ten'],
                'email'         => $input['email'],
                'so_dien_thoai' => $input['so_dien_thoai'] ?? null,
            ]);
            $this->repo->ganVaiTro($id, 'SINH_VIEN');
            $this->repo->transCommit();
        } catch (Throwable $e) {
            $this->repo->transRollback();
            throw $e;
        }

        return $this->layHoSo($id);
    }

    public function dangNhap(string $tenDangNhap, string $matKhau, ?string $diaChiIp = null): array
    {
        $nguoiDung = $this->repo->findByTenDangNhap($tenDangNhap);

        if (! $nguoiDung) {
            password_verify($matKhau, self::MA_BAM_GIA);
            $this->ghiNhatKy(null, 'LOGIN_FAILED', 'Dang nhap that bai: ten dang nhap khong ton tai (' . mb_substr($tenDangNhap, 0, 50) . ')', $diaChiIp);

            throw new UnauthenticatedException('Sai ten dang nhap hoac mat khau');
        }

        $id = (int) $nguoiDung['id'];

        if (! password_verify($matKhau, $nguoiDung['mat_khau_hash'])) {
            $this->ghiNhatKy($id, 'LOGIN_FAILED', 'Dang nhap that bai: sai mat khau', $diaChiIp);

            throw new UnauthenticatedException('Sai ten dang nhap hoac mat khau');
        }

        // Chi bao bi khoa khi da dung mat khau, de nguoi ngoai khong do duoc tai khoan nao bi khoa
        if ((int) $nguoiDung['bi_khoa'] === 1) {
            $this->ghiNhatKy($id, 'LOGIN_FAILED', 'Dang nhap that bai: tai khoan dang bi khoa', $diaChiIp);

            throw new ApiException(403, 'ACCOUNT_LOCKED', 'Tai khoan dang bi khoa, lien he thu ky khoa');
        }

        if (password_needs_rehash($nguoiDung['mat_khau_hash'], PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])) {
            $this->repo->updateMatKhauHash(
                (int) $nguoiDung['id'],
                password_hash($matKhau, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])
            );
        }

        $this->ghiNhatKy($id, 'LOGIN_SUCCESS', 'Dang nhap thanh cong', $diaChiIp);

        return $this->layHoSo($id);
    }

    /** Ghi nhat ky dang xuat; goi truoc khi huy phien */
    public function dangXuat(int $id, ?string $diaChiIp = null): void
    {
        $this->ghiNhatKy($id, 'LOGOUT', 'Dang xuat', $diaChiIp);
    }

    /** Ho so tra ve cho client, vai tro doi sang chu thuong cho khop tang Service */
    public function layHoSo(int $id): array
    {
        $hoSo            = $this->repo->findById($id);
        $hoSo['id']      = (int) $hoSo['id'];
        $hoSo['vai_tro'] =array_map('strtolower', $this->repo->getVaiTro($id));

        return $hoSo;
    }

    /** F1.5 - cap nhat ho so ca nhan: chi ho ten, email, so dien thoai; khong doi vai tro */
    public function capNhatHoSo(int $id, array $input): array
    {
        if (isset($input['email']) && $this->repo->existsEmailKhac($input['email'], $id)) {
            throw new ValidationException([['field' => 'email', 'issue' => 'Email da duoc su dung']]);
        }

        $this->repo->capNhatThongTin($id, $input);

        return $this->layHoSo($id);
    }

    /** F1.4 - doi mat khau, bat buoc dung mat khau cu */
    public function doiMatKhau(int $id, string $matKhauCu, string $matKhauMoi, ?string $diaChiIp = null): void
    {
        $hash = $this->repo->findMatKhauHash($id);

        if ($hash === null || ! password_verify($matKhauCu, $hash)) {
            throw new ValidationException([['field' => 'mat_khau_cu', 'issue' => 'Mat khau cu khong dung']]);
        }
        if (password_verify($matKhauMoi, $hash)) {
            throw new ValidationException([['field' => 'mat_khau_moi', 'issue' => 'Mat khau moi phai khac mat khau cu']]);
        }

        $this->repo->updateMatKhauHash($id, password_hash($matKhauMoi, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]));
        $this->ghiNhatKy($id, 'CHANGE_PASSWORD', 'Doi mat khau', $diaChiIp);
    }

    private function ghiNhatKy(?int $nguoiDungId, string $hanhDong, string $chiTiet, ?string $diaChiIp): void
    {
        if ($this->nhatKyRepo !== null) {
            $this->nhatKyRepo->ghiNhan($nguoiDungId, $hanhDong, $chiTiet, $diaChiIp);
        }
    }
}
