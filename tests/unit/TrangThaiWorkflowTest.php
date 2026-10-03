<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Services\TrangThaiWorkflow;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Kiem chung may trang thai T3 cua cac luong nghiep vu.
 */
final class TrangThaiWorkflowTest extends CIUnitTestCase
{
    private function deTaiWorkflow(): TrangThaiWorkflow
    {
        return new TrangThaiWorkflow([
            'pending' => [
                'duyet' => 'approved',
                'tu_choi' => 'rejected',
                'sua' => 'pending',
                'xoa' => null,
            ],
            'rejected' => [
                'sua' => 'pending',
                'xoa' => null,
            ],
            'approved' => [],
            'in_progress' => [],
            'completed' => [],
        ]);
    }

    private function baoCaoWorkflow(): TrangThaiWorkflow
    {
        return new TrangThaiWorkflow([
            'pending' => [
                'duyet' => 'approved',
                'yeu_cau_bo_sung' => 'revision_requested',
            ],
            'revision_requested' => [
                'nop_lai' => 'pending',
            ],
            'approved' => [],
            'overdue' => [],
        ]);
    }

    public function testDeTaiPendingDuocDuyetThanhApproved(): void
    {
        $this->assertSame('approved', $this->deTaiWorkflow()->next('pending', 'duyet'));
    }

    public function testDeTaiBiTuChoiCoTheSuaVaNopLai(): void
    {
        $workflow = $this->deTaiWorkflow();

        $this->assertSame('rejected', $workflow->next('pending', 'tu_choi'));
        $this->assertSame('pending', $workflow->next('rejected', 'sua'));
    }

    public function testDeTaiKhongChoPhepDuyetLaiTuApproved(): void
    {
        $this->expectException(ApiException::class);
        $this->deTaiWorkflow()->next('approved', 'duyet');
    }

    public function testBaoCaoDuocNopLaiSauKhiYeuCauBoSung(): void
    {
        $workflow = $this->baoCaoWorkflow();

        $this->assertSame('revision_requested', $workflow->next('pending', 'yeu_cau_bo_sung'));
        $this->assertSame('pending', $workflow->next('revision_requested', 'nop_lai'));
    }

    public function testBaoCaoKhongChoPhepNopLaiKhiChuaYeuCauBoSung(): void
    {
        $this->expectException(ApiException::class);

        $this->baoCaoWorkflow()->next('pending', 'nop_lai');
    }
}
