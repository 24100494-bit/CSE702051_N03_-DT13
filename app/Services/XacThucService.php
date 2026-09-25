<?php

namespace App\Services;

use App\Exceptions\UnauthenticatedException;
use App\Exceptions\ValidationException;
use App\Repositories\NguoiDungRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - dang ky, dang nhap
 * Mat khau bam bang bcrypt cost 12 qua ham co san password_hash (Muc 5.3 tai lieu ky thuat).
 */
class XacThucService
{
    private const BCRYPT_COST = 12;

    /**
     * Ma bam gia de van chay password_verify khi ten dang nhap khong ton tai,
     * tranh do thoi gian phan hoi ma biet duoc tai khoan nao co that.
     */
    private const MA_BAM_GIA = '$2y$12$f6PVy9lTz7QHHDcDcnvroO31dlX.03i0lD16gD4fdhg3MAEy2RX3m';

    protected NguoiDungRepository $repo;

    public function __construct(NguoiDungRepository $repo)
    {
        $this->repo = $repo;
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

    public function dangNhap(string $tenDangNhap, string $matKhau): array
    {
        $nguoiDung = $this->repo->findByTenDangNhap($tenDangNhap);

        if (! $nguoiDung) {
            password_verify($matKhau, self::MA_BAM_GIA);

            throw new UnauthenticatedException('Sai ten dang nhap hoac mat khau');
        }

        if (! password_verify($matKhau, $nguoiDung['mat_khau_hash'])) {
            throw new UnauthenticatedException('Sai ten dang nhap hoac mat khau');
        }

        if (password_needs_rehash($nguoiDung['mat_khau_hash'], PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])) {
            $this->repo->updateMatKhauHash(
                (int) $nguoiDung['id'],
                password_hash($matKhau, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST])
            );
        }

        return $this->layHoSo((int) $nguoiDung['id']);
    }

    /** Ho so tra ve cho client, vai tro doi sang chu thuong cho khop tang Service */
    public function layHoSo(int $id): array
    {
        $hoSo            = $this->repo->findById($id);
        $hoSo['id']      = (int) $hoSo['id'];
        $hoSo['vai_tro'] =array_map('strtolower', $this->repo->getVaiTro($id));

        return $hoSo;
    }
}
