<?php

namespace App\Services;

use App\Repositories\NhacHanRepository;
use App\Repositories\NhatKyHeThongRepository;
use App\Repositories\ThongBaoRepository;

/**
 * TANG NGHIEP VU (Service) - nhac han nop bao cao theo moc (K9, F6.3).
 * May chu mien phi khong co lich chay dinh ky, nen nhac han duoc kich hoat:
 *  - moi khi sinh vien mo danh sach thong bao (choSinhVien), chi tao cho chinh nguoi do;
 *  - qua diem cuoi noi bo co khoa bi mat (toanBo), tao cho moi thanh vien cua moi de tai.
 * Moi nguoi chi nhan mot nhac han cho moi cap (de tai, moc), nho dau nhan trong noi dung.
 */
class NhacHanService
{
    public const GIO_TRUOC_HAN = 48;
    public const TIEU_DE       = 'Nhắc hạn nộp báo cáo';

    public function __construct(
        private NhacHanRepository $repo,
        private ThongBaoRepository $thongBaoRepo,
        private ?NhatKyHeThongRepository $nhatKyRepo = null
    ) {
    }

    /** Tra ve so thong bao moi tao cho sinh vien */
    public function choSinhVien(int $sinhVienId): int
    {
        $so = 0;
        foreach ($this->repo->canNhacCuaSinhVien($sinhVienId, self::GIO_TRUOC_HAN) as $dong) {
            $so += $this->nhac($sinhVienId, $dong);
        }

        return $so;
    }

    /** Tac vu noi bo: nhac moi nguoi lien quan; tra ve so cap (de tai, moc) va so thong bao moi */
    public function toanBo(): array
    {
        $cap = $this->repo->canNhacToanBo(self::GIO_TRUOC_HAN);
        $so  = 0;
        foreach ($cap as $dong) {
            foreach ($this->repo->nguoiNhan((int) $dong['de_tai_id']) as $nguoiNhan) {
                $so += $this->nhac($nguoiNhan, $dong);
            }
        }

        if ($this->nhatKyRepo !== null) {
            $this->nhatKyRepo->ghiNhan(null, 'REMIND_DEADLINE', 'Tac vu nhac han: ' . count($cap) . ' cap de tai - moc, tao ' . $so . ' thong bao', null);
        }

        return ['so_cap_de_tai_moc' => count($cap), 'so_thong_bao_moi' => $so, 'truoc_han_gio' => self::GIO_TRUOC_HAN];
    }

    private function nhac(int $nguoiDungId, array $dong): int
    {
        $dauNhan = '(mốc ID ' . $dong['moc_id'] . ', đề tài ID ' . $dong['de_tai_id'] . ')';
        if ($this->repo->daNhac($nguoiDungId, self::TIEU_DE, $dauNhan)) {
            return 0;
        }

        $han = date('H:i d/m/Y', strtotime((string) $dong['han_nop']));
        $this->thongBaoRepo->create([
            'nguoi_dung_id' => $nguoiDungId,
            'tieu_de'       => self::TIEU_DE,
            'noi_dung'      => 'Mốc "' . $dong['ten_moc'] . '" của đề tài "' . $dong['ten_de_tai'] . '" hết hạn lúc ' . $han
                . ', nhóm chưa nộp báo cáo. ' . $dauNhan,
        ]);

        return 1;
    }
}
