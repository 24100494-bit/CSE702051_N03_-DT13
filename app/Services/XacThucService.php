<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\NotFoundException;
use App\Exceptions\TooManyRequestsException;
use App\Exceptions\UnauthenticatedException;
use App\Exceptions\ValidationException;
use App\Repositories\GioiHanDangNhapRepository;
use App\Repositories\MaDatLaiRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThongBaoRepository;
use Throwable;

/**
 * TANG NGHIEP VU (Service) - dang ky, dang nhap
 * Mat khau bam bang bcrypt cost 12 qua ham co san password_hash (Muc 5.3 tai lieu ky thuat).
 * Dang nhap thanh cong/that bai, dang xuat, doi mat khau duoc ghi nhat ky he thong (khoi K8); khong bao gio ghi mat khau.
 * Chong do mat khau (BM10): sai SO_LAN_SAI_TOI_DA lan trong CUA_SO_GIAY giay thi lan sau tra 429,
 * dem rieng theo dia chi IP va theo tai khoan; lan bi chan ghi LOGIN_BLOCKED.
 * Khoi phuc mat khau (K1, F1.3): may chu khong gui duoc email nen nguoi dung gui yeu cau, thu ky khoa
 * kiem danh tinh roi cap ma dat lai mot lan (het han sau MA_DAT_LAI_PHUT phut), nguoi dung nhap ma de dat mat khau moi.
 */
class XacThucService
{
    public const BCRYPT_COST = 12;

    public const SO_LAN_SAI_TOI_DA = 5;
    public const CUA_SO_GIAY       = 900;

    public const MA_DAT_LAI_PHUT            = 15;
    public const SO_YEU_CAU_DAT_LAI_MOI_GIO = 3;

    /** Bo ky tu cua ma dat lai: bo 0/O, 1/I/L de doc cho nhau khong nham */
    private const KY_TU_MA = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const DO_DAI_MA = 8;

    /**
     * Ma bam gia de van chay password_verify khi ten dang nhap khong ton tai,
     * tranh do thoi gian phan hoi ma biet duoc tai khoan nao co that.
     */
    private const MA_BAM_GIA = '$2y$12$f6PVy9lTz7QHHDcDcnvroO31dlX.03i0lD16gD4fdhg3MAEy2RX3m';

    protected NguoiDungRepository $repo;
    protected ?NhatKyHeThongRepository $nhatKyRepo;
    protected ?GioiHanDangNhapRepository $gioiHanRepo;
    protected ?MaDatLaiRepository $maDatLaiRepo;
    protected ?ThongBaoRepository $thongBaoRepo;

    public function __construct(
        NguoiDungRepository $repo,
        ?NhatKyHeThongRepository $nhatKyRepo = null,
        ?GioiHanDangNhapRepository $gioiHanRepo = null,
        ?MaDatLaiRepository $maDatLaiRepo = null,
        ?ThongBaoRepository $thongBaoRepo = null
    ) {
        $this->repo         = $repo;
        $this->nhatKyRepo   = $nhatKyRepo;
        $this->gioiHanRepo  = $gioiHanRepo;
        $this->maDatLaiRepo = $maDatLaiRepo;
        $this->thongBaoRepo = $thongBaoRepo;
    }

    /**
     * F1.3 buoc 1 - nguoi dung bao quen mat khau. Phan hoi o Controller luon giong nhau du email co ton tai hay khong.
     * Email co that: bao cho moi thu ky khoa (toi da SO_YEU_CAU_DAT_LAI_MOI_GIO lan moi gio de khong bi spam).
     */
    public function quenMatKhau(string $email, ?string $diaChiIp = null): void
    {
        $nguoiDung = $this->repo->findByEmail($email);

        if (! $nguoiDung) {
            $this->ghiNhatKy(null, 'RESET_PASSWORD_REQUEST', 'Yeu cau dat lai mat khau cho email khong ton tai', $diaChiIp);

            return;
        }

        $id = (int) $nguoiDung['id'];
        if ($this->gioiHanRepo !== null
            && $this->gioiHanRepo->demTheoHanhDongVaNguoiDung('RESET_PASSWORD_REQUEST', $id, 3600) >= self::SO_YEU_CAU_DAT_LAI_MOI_GIO) {
            return;
        }

        $this->ghiNhatKy($id, 'RESET_PASSWORD_REQUEST', 'Yeu cau dat lai mat khau', $diaChiIp);

        if ($this->thongBaoRepo !== null) {
            foreach ($this->repo->findIdTheoVaiTro('THU_KY_KHOA') as $thuKyId) {
                $this->thongBaoRepo->create([
                    'nguoi_dung_id' => $thuKyId,
                    'tieu_de'       => 'Yêu cầu đặt lại mật khẩu',
                    'noi_dung'      => 'Tài khoản ' . $nguoiDung['ten_dang_nhap'] . ' (' . $nguoiDung['ho_ten'] . ', ' . $nguoiDung['email'] . ', mã ' . $id
                        . ') gửi yêu cầu đặt lại mật khẩu. Kiểm tra đúng người rồi cấp mã đặt lại ở khối Tài khoản người dùng.',
                ]);
            }
        }
    }

    /** F1.3 buoc 2 (F1.6) - thu ky khoa cap ma dat lai mot lan; ma chi tra ve mot lan, CSDL chi luu ban bam */
    public function capMaDatLai(int $id, array $currentUser): array
    {
        $nguoiDung = $this->repo->findById($id);
        if (! $nguoiDung) {
            throw new NotFoundException('Khong tim thay nguoi dung');
        }

        $ma = '';
        for ($i = 0; $i < self::DO_DAI_MA; $i++) {
            $ma .= self::KY_TU_MA[random_int(0, strlen(self::KY_TU_MA) - 1)];
        }
        $hetHan = $this->maDatLaiRepo->taoMa($id, hash('sha256', $ma), self::MA_DAT_LAI_PHUT, (int) $currentUser['id']);

        $this->ghiNhatKy((int) $currentUser['id'], 'RESET_CODE_ISSUED', 'Cap ma dat lai mat khau cho nguoi dung ID: ' . $id . ', het han ' . $hetHan, $currentUser['ip'] ?? null);

        return [
            'nguoi_dung_id' => $id,
            'ten_dang_nhap' => $nguoiDung['ten_dang_nhap'],
            'ma'            => substr($ma, 0, 4) . '-' . substr($ma, 4),
            'het_han'       => $hetHan,
            'hieu_luc_phut' => self::MA_DAT_LAI_PHUT,
        ];
    }

    /**
     * F1.3 buoc 3 - dat mat khau moi bang ma. Ma dung mot lan; sai SO_LAN_SAI_TOI_DA lan trong CUA_SO_GIAY giay
     * tu mot dia chi IP thi tra 429 (chong do ma).
     */
    public function datLaiMatKhau(string $maNhap, string $matKhauMoi, ?string $diaChiIp = null): void
    {
        if ($this->gioiHanRepo !== null && $diaChiIp !== null && $diaChiIp !== '') {
            $tuoi = $this->gioiHanRepo->tuoiTheoHanhDongVaIp('RESET_PASSWORD_FAILED', $diaChiIp, self::CUA_SO_GIAY);
            if (count($tuoi) >= self::SO_LAN_SAI_TOI_DA) {
                $cho = self::CUA_SO_GIAY - $tuoi[self::SO_LAN_SAI_TOI_DA - 1];

                throw new TooManyRequestsException('Nhap sai ma qua nhieu lan, vui long thu lai sau ' . max(1, (int) ceil($cho / 60)) . ' phut', $cho);
            }
        }

        $ma = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $maNhap));

        $this->repo->transBegin();
        try {
            $ban = strlen($ma) === self::DO_DAI_MA ? $this->maDatLaiRepo->timMaConHieuLucForUpdate(hash('sha256', $ma)) : null;
            if ($ban === null) {
                $this->repo->transRollback();
                $this->ghiNhatKy(null, 'RESET_PASSWORD_FAILED', 'Ma dat lai khong dung hoac da het han', $diaChiIp);

                throw new ValidationException([['field' => 'token', 'issue' => 'Ma dat lai khong dung hoac da het han']]);
            }

            $nguoiDungId = (int) $ban['nguoi_dung_id'];
            $this->repo->updateMatKhauHash($nguoiDungId, password_hash($matKhauMoi, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]));
            $this->maDatLaiRepo->huyMaCuaNguoiDung($nguoiDungId);
            $this->repo->transCommit();
        } catch (ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->repo->transRollback();

            throw $e;
        }

        $this->ghiNhatKy($nguoiDungId, 'RESET_PASSWORD', 'Dat lai mat khau bang ma mot lan', $diaChiIp);
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
        $nguoiDung          = $this->repo->findByTenDangNhap($tenDangNhap);
        $chiTietKhongTonTai = 'Dang nhap that bai: ten dang nhap khong ton tai (' . mb_substr($tenDangNhap, 0, 50) . ')';

        $this->kiemGioiHan($nguoiDung ? (int) $nguoiDung['id'] : null, $tenDangNhap, $chiTietKhongTonTai, $diaChiIp);

        if (! $nguoiDung) {
            password_verify($matKhau, self::MA_BAM_GIA);
            $this->ghiNhatKy(null, 'LOGIN_FAILED', $chiTietKhongTonTai, $diaChiIp);

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

    /**
     * Chan truoc khi kiem mat khau, ke ca khi mat khau dung. Ten dang nhap khong ton tai cung bi dem va chan
     * nhu tai khoan that, de khong do duoc tai khoan nao co that qua ma 429.
     */
    private function kiemGioiHan(?int $nguoiDungId, string $tenDangNhap, string $chiTietKhongTonTai, ?string $diaChiIp): void
    {
        if ($this->gioiHanRepo === null) {
            return;
        }

        $danhSach = [];
        if ($diaChiIp !== null && $diaChiIp !== '') {
            $danhSach['dia chi IP'] = $this->gioiHanRepo->tuoiLanSaiTheoIp($diaChiIp, self::CUA_SO_GIAY);
        }
        $danhSach['tai khoan'] = $nguoiDungId !== null
            ? $this->gioiHanRepo->tuoiLanSaiTheoTaiKhoan($nguoiDungId, self::CUA_SO_GIAY)
            : $this->gioiHanRepo->tuoiLanSaiTheoChiTiet($chiTietKhongTonTai, self::CUA_SO_GIAY);

        $theo = [];
        $cho  = 0;
        foreach ($danhSach as $ten => $tuoi) {
            if (count($tuoi) >= self::SO_LAN_SAI_TOI_DA) {
                $theo[] = $ten;
                // Het chan khi lan sai thu SO_LAN_SAI_TOI_DA tinh tu moi nhat ra khoi cua so
                $cho = max($cho, self::CUA_SO_GIAY - $tuoi[self::SO_LAN_SAI_TOI_DA - 1]);
            }
        }

        if ($theo) {
            $this->ghiNhatKy(
                $nguoiDungId,
                'LOGIN_BLOCKED',
                'Chan dang nhap: sai ' . self::SO_LAN_SAI_TOI_DA . ' lan trong ' . (self::CUA_SO_GIAY / 60) . ' phut theo ' . implode(' va ', $theo) . ' (' . mb_substr($tenDangNhap, 0, 50) . ')',
                $diaChiIp
            );

            throw new TooManyRequestsException('Dang nhap sai qua nhieu lan, vui long thu lai sau ' . max(1, (int) ceil($cho / 60)) . ' phut', $cho);
        }
    }

    private function ghiNhatKy(?int $nguoiDungId, string $hanhDong, string $chiTiet, ?string $diaChiIp): void
    {
        if ($this->nhatKyRepo !== null) {
            $this->nhatKyRepo->ghiNhan($nguoiDungId, $hanhDong, $chiTiet, $diaChiIp);
        }
    }
}
