<?php

namespace Tests\Unit;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Repositories\NhatKyHeThongRepository;
use App\Services\NhatKyHeThongService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Kiem thu don vi cho NhatKyHeThongService (K8 - V3)
 */
final class NhatKyHeThongServiceTest extends CIUnitTestCase
{
    private function taoService(array $mockItems = [], int $mockTotal = 0): NhatKyHeThongService
    {
        $repoMock = $this->createMock(NhatKyHeThongRepository::class);
        $repoMock->method('findAllPaginated')
            ->willReturn([
                'items'       => $mockItems,
                'total'       => $mockTotal,
                'page'        => 1,
                'size'        => 20,
                'total_pages' => $mockTotal > 0 ? 1 : 0,
            ]);

        return new NhatKyHeThongService($repoMock);
    }

    public function testNguoiDungKhongPhaiThuKyKhoaBiTuChoi(): void
    {
        $this->expectException(ForbiddenException::class);

        $service = $this->taoService();
        $userKhongHopLe = ['id' => 2, 'danh_sach_vai_tro' => ['SINH_VIEN']];

        $service->traCuu(1, 20, null, null, null, null, null, $userKhongHopLe);
    }

    public function testSizeVuotGiaTriToiDaBiLoiValidation(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $thuKy = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        $service->traCuu(1, 500, null, null, null, null, null, $thuKy);
    }

    public function testTuNgayLonHonDenNgayBiLoiValidation(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $thuKy = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        $service->traCuu(1, 20, null, null, null, '2026-10-10', '2026-10-01', $thuKy);
    }

    public function testFormatNgayKhongHopLeBiLoiValidation(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $thuKy = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        $service->traCuu(1, 20, null, null, null, '10/10/2026', null, $thuKy);
    }

    public function testThuKyKhoaTraCuuThanhCong(): void
    {
        $mockData = [
            ['id' => 1, 'hanh_dong' => 'PROPOSE_TOPIC', 'chi_tiet' => 'De xuat de tai ID: 1']
        ];
        $service = $this->taoService($mockData, 1);
        $thuKy = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        $result = $service->traCuu(1, 20, null, null, null, '2026-10-01', '2026-10-10', $thuKy);

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
    }
}
