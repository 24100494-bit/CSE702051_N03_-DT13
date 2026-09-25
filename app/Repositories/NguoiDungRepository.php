<?php

namespace App\Repositories;

/**
 * Tang truy cap du lieu cho bang `nguoi_dung`.
 * Dung chung cho moi vai tro (SV, GVHD, Thu ky, Hoi dong); vai tro cu the
 * xac dinh qua bang trung gian `nguoi_dung_vai_tro` (xem VaiTroRepository).
 * Chi findByTenDangNhap() lay cot mat_khau_hash (de dang nhap); cac ham doc khac khong lay (BM6).
 */
class NguoiDungRepository extends BaseRepository
{
    protected string $table = 'nguoi_dung';

    protected array $allowedFields = ['ten_dang_nhap', 'mat_khau_hash', 'ho_ten', 'email', 'so_dien_thoai'];

    private const COT_CONG_KHAI = 'id, ten_dang_nhap, ho_ten, email, so_dien_thoai, created_at';

    public function findByTenDangNhap(string $tenDangNhap): ?array
    {
        $row = $this->db->table('nguoi_dung')
            ->select('id, ten_dang_nhap, mat_khau_hash, ho_ten, email')
            ->where('ten_dang_nhap', $tenDangNhap)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('nguoi_dung')
            ->select(self::COT_CONG_KHAI)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->db->table('nguoi_dung')
            ->select(self::COT_CONG_KHAI)
            ->where('email', $email)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function existsTenDangNhap(string $tenDangNhap): bool
    {
        return $this->db->table('nguoi_dung')->where('ten_dang_nhap', $tenDangNhap)->countAllResults() > 0;
    }

    public function existsEmail(string $email): bool
    {
        return $this->db->table('nguoi_dung')->where('email', $email)->countAllResults() > 0;
    }

    /** Danh sach vai tro (id, ten_vai_tro) cua mot nguoi dung */
    public function findVaiTroCuaNguoiDung(int $nguoiDungId): array
    {
        return $this->db->table('vai_tro vt')
            ->select('vt.id, vt.ten_vai_tro')
            ->join('nguoi_dung_vai_tro ndvt', 'ndvt.vai_tro_id = vt.id')
            ->where('ndvt.nguoi_dung_id', $nguoiDungId)
            ->orderBy('vt.id')
            ->get()
            ->getResultArray();
    }

    /** @return list<string> chi ten vai tro, vi du ['SINH_VIEN'] */
    public function getVaiTro(int $nguoiDungId): array
    {
        return array_column($this->findVaiTroCuaNguoiDung($nguoiDungId), 'ten_vai_tro');
    }

    public function create(array $data): int
    {
        // $data: ten_dang_nhap, mat_khau_hash, ho_ten, email, [so_dien_thoai]
        // mat_khau_hash phai la ket qua bam tu tang Service, Repository khong bam mat khau.
        return $this->insert($data);
    }

    /** Chi cap nhat thong tin ho so; doi mat khau dung updateMatKhauHash() */
    public function capNhatThongTin(int $id, array $data): bool
    {
        return $this->update($id, array_intersect_key($data, array_flip(['ho_ten', 'email', 'so_dien_thoai'])));
    }

    public function updateMatKhauHash(int $id, string $hash): bool
    {
        return $this->update($id, ['mat_khau_hash' => $hash]);
    }

    public function ganVaiTro(int $nguoiDungId, string $tenVaiTro): void
    {
        $vaiTro = $this->db->table('vai_tro')
            ->select('id')
            ->where('ten_vai_tro', $tenVaiTro)
            ->get()
            ->getRowArray();

        $this->db->table('nguoi_dung_vai_tro')->insert([
            'nguoi_dung_id' => $nguoiDungId,
            'vai_tro_id'    => $vaiTro['id'],
        ]);
    }
}
