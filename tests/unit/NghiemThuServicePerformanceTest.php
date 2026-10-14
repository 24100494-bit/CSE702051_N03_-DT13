<?php

namespace Tests\Unit;

use App\Repositories\BaoCaoMocRepository;
use App\Repositories\DeTaiRepository;
use App\Repositories\DiemNghiemThuRepository;
use App\Repositories\MocThoiGianRepository;
use App\Repositories\NguoiDungRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThanhVienNhomRepository;
use App\Repositories\ThongBaoRepository;
use App\Services\NghiemThuService;
use CodeIgniter\Test\CIUnitTestCase;

final class NghiemThuServicePerformanceTest extends CIUnitTestCase
{
    public function testDanhSachHoSoGomThongKeDiemVaDaChamTheoLo(): void
    {
        $userId = 987654321;
        cache()->save('nghiem_thu_danh_sach_version', random_int(1000000, 2000000000), 86400);

        $deTaiRepo = $this->createMock(DeTaiRepository::class);
        $deTaiRepo->expects($this->once())
            ->method('findTheoPhamVi')
            ->with(1, 20, 'completed', null, null, ['completed', 'accepted'])
            ->willReturn([
                'items' => [
                    ['id' => 11, 'ten_de_tai' => 'De tai A'],
                    ['id' => 12, 'ten_de_tai' => 'De tai B'],
                ],
                'total' => 2,
                'page' => 1,
                'size' => 20,
            ]);

        $diemRepo = $this->createMock(DiemNghiemThuRepository::class);
        $diemRepo->expects($this->once())
            ->method('thongKeTheoNhieuDeTai')
            ->with([11, 12])
            ->willReturn([
                11 => ['so_luot_cham' => 2, 'diem_trung_binh' => 8.5],
            ]);
        $diemRepo->expects($this->once())
            ->method('cacDeTaiDaCham')
            ->with([11, 12], $userId)
            ->willReturn([12]);

        $service = new NghiemThuService(
            $deTaiRepo,
            $diemRepo,
            $this->createMock(ThanhVienNhomRepository::class),
            $this->createMock(BaoCaoMocRepository::class),
            $this->createMock(MocThoiGianRepository::class),
            $this->createMock(NguoiDungRepository::class),
            $this->createMock(NhatKyHeThongRepository::class),
            $this->createMock(ThongBaoRepository::class)
        );

        $result = $service->danhSachHoSo(1, 20, 'completed', ['id' => $userId]);

        $this->assertSame(2, $result['items'][0]['so_luot_cham']);
        $this->assertSame(8.5, $result['items'][0]['diem_trung_binh']);
        $this->assertFalse($result['items'][0]['toi_da_cham']);
        $this->assertSame(0, $result['items'][1]['so_luot_cham']);
        $this->assertNull($result['items'][1]['diem_trung_binh']);
        $this->assertTrue($result['items'][1]['toi_da_cham']);

        // Cung user/bo loc trong TTL: dung cache, khong truy van lai repository.
        $cached = $service->danhSachHoSo(1, 20, 'completed', ['id' => $userId]);
        $this->assertSame($result, $cached);
    }
}
