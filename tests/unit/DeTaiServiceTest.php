<?php

namespace Tests\Unit;

use App\Exceptions\ValidationException;
use App\Exceptions\ForbiddenException;
use App\Repositories\DeTaiRepository;
use App\Repositories\ThanhVienNhomRepository;
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
    public function testChuDeTaiDuocXemChiTietDeTaiCuaMinh(): void
    {
        $deTaiRepo = $this->createMock(DeTaiRepository::class);
        $deTaiRepo->method('findById')->with(10)->willReturn([
            'id' => 10,
            'sinh_vien_de_xuat_id' => 7,
            'gvhd_id' => 12,
            'trang_thai' => 'in_progress',
        ]);
        $thanhVienRepo = $this->createMock(ThanhVienNhomRepository::class);
        $service = new DeTaiService($deTaiRepo, $thanhVienRepo);

        $ketQua = $service->getForUser(10, ['id' => 7, 'danh_sach_vai_tro' => ['SINH_VIEN']]);

        $this->assertSame(10, $ketQua['id']);
    }

    public function testThanhVienKhacKhongDuocXemChiTietNeuKhongPhaiChuDeTai(): void
    {
        $deTaiRepo = $this->createMock(DeTaiRepository::class);
        $deTaiRepo->method('findById')->with(10)->willReturn([
            'id' => 10,
            'sinh_vien_de_xuat_id' => 7,
            'gvhd_id' => 12,
            'trang_thai' => 'in_progress',
        ]);
        $thanhVienRepo = $this->createMock(ThanhVienNhomRepository::class);
        $thanhVienRepo->expects($this->never())->method('laThanhVien');
        $service = new DeTaiService($deTaiRepo, $thanhVienRepo);

        $this->expectException(ForbiddenException::class);
        $service->getForUser(10, ['id' => 8, 'danh_sach_vai_tro' => ['SINH_VIEN']]);
    }

}
