<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho trang cong khai (K10, F2.1): chi doc de tai da duoc duyet tro len.
 * Chi chon cot cong khai; khong tra ma nguoi de xuat, ma GVHD hay thong tin ca nhan.
 */
class CongKhaiRepository extends BaseRepository
{
    protected string $table = 'de_tai';

    public const TRANG_THAI_CONG_KHAI = ['approved', 'in_progress', 'completed', 'accepted'];

    public function deTaiDaDuyet(int $page, int $size, ?string $tuKhoa = null): array
    {
        $builder = $this->db->table('de_tai dt')
            ->select('dt.id, dt.ten_de_tai, dt.mo_ta_pham_vi, dt.trang_thai, dt.created_at, lhp.ma_lop, lhp.ten_lop, lhp.nam_hoc, lhp.hoc_ky')
            ->join('lop_hoc_phan lhp', 'lhp.id = dt.lop_hoc_phan_id', 'left')
            ->whereIn('dt.trang_thai', self::TRANG_THAI_CONG_KHAI);

        if ($tuKhoa !== null && $tuKhoa !== '') {
            $builder->groupStart()
                ->like('dt.ten_de_tai', $tuKhoa)
                ->orLike('dt.mo_ta_pham_vi', $tuKhoa)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('dt.created_at', 'DESC')
            ->orderBy('dt.id', 'DESC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return ['items' => $rows, 'total' => $total];
    }
}
