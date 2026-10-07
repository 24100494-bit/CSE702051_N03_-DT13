<?php

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Repositories\DeTaiRepository;
use App\Services\DeTaiService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Kiem thu don vi cho chuc nang tim kiem, loc, phan trang DeTaiService (K5 - V3)
 */
final class DeTaiServiceTest extends CIUnitTestCase
{
    private function taoService(): DeTaiService
    {
        $repoMock = $this->createMock(DeTaiRepository::class);
        return new DeTaiService($repoMock);
    }

    public function testTimKiemDeTaiCotSapXepKhongHopLe(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $user = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        // 'ip_address' khong nam trong whitelist cot sap xep
        $service->listForUser(1, 20, null, null, null, 'ip_address', 'DESC', $user);
    }

    public function testTimKiemDeTaiHuongSapXepKhongHopLe(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $user = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        // 'SIDEWAYS' khong phai ASC hay DESC
        $service->listForUser(1, 20, null, null, null, 'created_at', 'SIDEWAYS', $user);
    }

    public function testTimKiemDeTaiTrangThaiKhongHopLe(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->taoService();
        $user = ['id' => 1, 'danh_sach_vai_tro' => ['THU_KY_KHOA']];

        $service->listForUser(1, 20, 'INVALID_STATUS', null, null, 'created_at', 'DESC', $user);
    }
}
