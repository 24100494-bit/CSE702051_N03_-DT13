<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Tang truy cap du lieu cho bang `nhat_ky_he_thong` (audit log).
 * Chi cung cap thao tac GHI va DOC - khong ke thua BaseRepository, khong co update/delete
 * de dam bao tinh toan ven cua nhat ky (yeu cau bat buoc cho Buoi 07 va Buoi 08).
 */
class NhatKyHeThongRepository
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function ghiNhan(?int $nguoiDungId, string $hanhDong, ?string $chiTiet = null, ?string $diaChiIp = null): int
    {
        $this->db->table('nhat_ky_he_thong')->insert([
            'nguoi_dung_id' => $nguoiDungId,
            'hanh_dong'     => $hanhDong,
            'chi_tiet'      => $chiTiet,
            'dia_chi_ip'    => $diaChiIp,
        ]);

        return (int) $this->db->insertID();
    }

    public function findGanDayTheoHanhDong(string $hanhDong, int $gioiHan = 50): array
    {
        return $this->db->table('nhat_ky_he_thong')
            ->where('hanh_dong', $hanhDong)
            ->orderBy('created_at', 'DESC')
            ->limit($gioiHan)
            ->get()
            ->getResultArray();
    }

    /** Cac dong nhat ky lam doi trang thai de tai; khop dung ma (ID 1 khong khop 14, 10...) */
    public function findLichSuDeTai(int $deTaiId): array
    {
        return $this->findTheoDoiTuong('de tai ID: ' . $deTaiId, ['PROPOSE_TOPIC', 'APPROVE_TOPIC', 'REJECT_TOPIC', 'RESUBMIT_TOPIC', 'START_TOPIC', 'TOPIC_READY', 'ACCEPT_TOPIC']);
    }

    /** Cac dong nhat ky lam doi trang thai mot ban bao cao; khop dung ma */
    public function findLichSuBaoCao(int $baoCaoId): array
    {
        return $this->findTheoDoiTuong('bao cao ID: ' . $baoCaoId, ['SUBMIT_REPORT', 'RESUBMIT_REPORT', 'APPROVE_REPORT', 'REQUEST_REVISION']);
    }

    /**
     * Loc theo hanh dong va chuoi "<doi tuong> ID: <ma>" theo sau khong phai chu so.
     * Ma la so nguyen do Service truyen vao nen an toan khi dat vao mau REGEXP.
     */
    private function findTheoDoiTuong(string $mau, array $hanhDong): array
    {
        return $this->db->table('nhat_ky_he_thong')
            ->whereIn('hanh_dong', $hanhDong)
            ->where('chi_tiet REGEXP ' . $this->db->escape($mau . '([^0-9]|$)'), null, false)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }
    /** Tra cuu nhat ky co phan trang va nhieu dieu kien loc. */
    public function findAllPaginated(
        int $page,
        int $size,
        ?int $nguoiDungId = null,
        ?string $hanhDong = null,
        ?string $tuKhoa = null,
        ?string $tuNgay = null,
        ?string $denNgay = null
    ): array
    {
        $builder = $this->db->table('nhat_ky_he_thong nk')
            ->select('nk.*, nd.ho_ten, nd.ten_dang_nhap')
            ->join('nguoi_dung nd', 'nd.id = nk.nguoi_dung_id', 'left');

        if ($nguoiDungId !== null) {
            $builder->where('nk.nguoi_dung_id', $nguoiDungId);
        }
        if ($hanhDong !== null && trim($hanhDong) !== '') {
            $builder->where('nk.hanh_dong', trim($hanhDong));
        }
        if ($tuKhoa !== null && trim($tuKhoa) !== '') {
            $tuKhoa = trim($tuKhoa);
            $builder->groupStart()
                ->like('nk.hanh_dong', $tuKhoa)
                ->orLike('nk.chi_tiet', $tuKhoa)
                ->orLike('nk.dia_chi_ip', $tuKhoa)
                ->orLike('nd.ho_ten', $tuKhoa)
                ->orLike('nd.ten_dang_nhap', $tuKhoa)
                ->groupEnd();
        }
        if ($tuNgay !== null && $tuNgay !== '') {
            $builder->where('nk.created_at >=', $tuNgay . ' 00:00:00');
        }
        if ($denNgay !== null && $denNgay !== '') {
            $builder->where('nk.created_at <=', $denNgay . ' 23:59:59');
        }

        $total = $builder->countAllResults(false);
        $rows = $builder
            ->orderBy('nk.created_at', 'DESC')
            ->orderBy('nk.id', 'DESC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return [
            'items'       => $rows,
            'total'       => $total,
            'page'        => $page,
            'size'        => $size,
            'total_pages' => $total > 0 ? (int) ceil($total / $size) : 0,
        ];
    }

}
