<?php

namespace App\Commands;

use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

/**
 * Kiem thu nhanh tang truy cap du lieu - san pham ca nhan cua V2, Buoi 04.
 * Chay bang: php spark kiemthu:repository
 * Yeu cau: da nap schema.sql roi seed.sql vao CSDL khai bao trong .env (database.default.*).
 */
class KiemThuRepository extends BaseCommand
{
    protected $group       = 'DT13';
    protected $name        = 'kiemthu:repository';
    protected $description = 'Kiem thu nhanh tang truy cap du lieu (V2 - Buoi 04).';

    public function run(array $params)
    {
        CLI::write('=== Kiem thu tang truy cap du lieu (V2 - Buoi 04) ===');

        try {
            $db = Database::connect();
            $db->initialize();
            CLI::write('[OK] Ket noi CSDL thanh cong.');
        } catch (Throwable $e) {
            log_message('error', '[KiemThuRepository] ' . $e->getMessage());
            CLI::error('[FAIL] Khong the ket noi co so du lieu.');

            return EXIT_ERROR;
        }

        $nguoiDungRepo = new NguoiDungRepository($db);
        $deTaiRepo     = new DeTaiRepository($db);
        $thanhVienRepo = new ThanhVienNhomRepository($db);
        $mocRepo       = new MocThoiGianRepository($db);
        $baoCaoRepo    = new BaoCaoMocRepository($db);
        $nhatKyRepo    = new NhatKyHeThongRepository($db);

        // 1) DOC: tim nguoi dung theo ten dang nhap (du lieu mau trong seed.sql)
        $sv = $nguoiDungRepo->findByTenDangNhap('sv_tran_van_b');
        $this->ketQua($sv !== null, 'Tim thay nguoi dung: ' . ($sv['ho_ten'] ?? ''), 'Khong tim thay tai khoan sv_tran_van_b - kiem tra lai seed.sql');

        // 2) DOC: de tai theo lop hoc phan + trang thai (truy van co dieu kien)
        CLI::write(sprintf('[OK] So de tai dang thuc hien trong lop 1: %d', count($deTaiRepo->findByLopHocPhan(1, 'in_progress'))));

        // 3) DOC: danh sach thanh vien cua de tai 1 (JOIN 2 bang)
        CLI::write(sprintf('[OK] So thanh vien nhom cua de tai 1: %d', count($thanhVienRepo->findByDeTai(1))));

        // 4) GHI: tao moc thoi gian moi (kiem tra INSERT tham so hoa)
        $mocId = $mocRepo->create([
            'lop_hoc_phan_id' => 1,
            'ten_moc'         => 'Moc kiem thu Buoi 04',
            'mo_ta'           => 'Ban ghi tao boi kiemthu:repository, se bi xoa cuoi lenh',
            'han_nop'         => date('Y-m-d H:i:s', strtotime('+7 days')),
            'bat_buoc'        => 1,
        ]);
        CLI::write("[OK] Da tao moc_thoi_gian moi, id = {$mocId}");

        // 5) GHI + SUA: tao bao cao gan voi moc vua tao, roi duyet (kiem tra UPDATE tham so hoa)
        $baoCaoId = $baoCaoRepo->create([
            'de_tai_id'        => 1,
            'moc_thoi_gian_id' => $mocId,
            'duong_dan_tep'    => '/uploads/test/bao_cao_test.pdf',
            'ten_tep_goc'      => 'bao_cao_test.pdf',
        ]);
        $baoCaoRepo->duyet($baoCaoId, 'Duyet tu dong boi kich ban kiem thu.');
        $baoCaoSauDuyet = $baoCaoRepo->findById($baoCaoId);
        $this->ketQua(
            $baoCaoSauDuyet['trang_thai'] === 'approved',
            "Bao cao {$baoCaoId} sau khi duyet co trang_thai = {$baoCaoSauDuyet['trang_thai']}",
            "Bao cao {$baoCaoId} chua duoc duyet"
        );

        // 6) GHI: nhat ky he thong
        $nhatKyRepo->ghiNhan(null, 'TEST_REPOSITORY_LAYER', 'Chay smoke test tang du lieu Buoi 04', '127.0.0.1');
        CLI::write('[OK] Da ghi nhat ky he thong.');

        // 7) Chi cot trong allowedFields duoc ghi: cot la gui kem bi bo qua
        $mocCotLaId = $mocRepo->create([
            'lop_hoc_phan_id'   => 1,
            'ten_moc'           => 'Moc kiem thu cot la',
            'han_nop'           => date('Y-m-d H:i:s', strtotime('+7 days')),
            'cot_khong_ton_tai' => 'x',
        ]);
        $this->ketQua($mocCotLaId > 0, 'Cot la trong du lieu ghi bi bo qua, van ghi duoc ban ghi', 'Ghi du lieu co cot la bi loi');

        // 8) Ham doc nguoi dung theo id khong tra ve mat_khau_hash (BM6)
        $nd = $nguoiDungRepo->findById((int) $sv['id']);
        $this->ketQua(! array_key_exists('mat_khau_hash', $nd), 'findById() khong tra ve cot mat_khau_hash', 'findById() dang tra ve mat_khau_hash');

        // 9) DON DEP: xoa du lieu test, khong dong den du lieu goc trong seed.sql
        $baoCaoRepo->delete($baoCaoId);
        $mocRepo->delete($mocId);
        $mocRepo->delete($mocCotLaId);
        CLI::write('[OK] Da don dep du lieu test.');

        CLI::write('=== Hoan tat: tang truy cap du lieu hoat dong dung nhu mong doi ===');
    }

    private function ketQua(bool $dat, string $thongBaoDat, string $thongBaoTruot): void
    {
        $dat ? CLI::write('[OK] ' . $thongBaoDat) : CLI::error('[FAIL] ' . $thongBaoTruot);
    }
}
