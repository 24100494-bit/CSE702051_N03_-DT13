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

    protected array $allowedFields = ['ten_dang_nhap', 'mat_khau_hash', 'ho_ten', 'email', 'so_dien_thoai', 'bi_khoa'];

    private const COT_CONG_KHAI = 'id, ten_dang_nhap, ho_ten, email, so_dien_thoai, bi_khoa, created_at';

    public function findByTenDangNhap(string $tenDangNhap): ?array
    {
        $row = $this->db->table('nguoi_dung')
            ->select('id, ten_dang_nhap, mat_khau_hash, ho_ten, email, bi_khoa')
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

    /** Ma bam mat khau theo id, chi dung khi doi mat khau (BM6: khong tra ra ngoai) */
    public function findMatKhauHash(int $id): ?string
    {
        $row = $this->db->table('nguoi_dung')->select('mat_khau_hash')->where('id', $id)->get()->getRowArray();

        return $row['mat_khau_hash'] ?? null;
    }

    /**
     * Danh sach nguoi dung co phan trang, loc theo vai tro va tu khoa (ten dang nhap, ho ten, email).
     * Moi dong kem chuoi vai_tro (cac ten vai tro noi bang dau phay).
     */
    public function findTheoBoLoc(int $page, int $size, ?string $tenVaiTro = null, ?string $tuKhoa = null): array
    {
        $builder = $this->db->table('nguoi_dung nd');

        if ($tenVaiTro !== null) {
            $builder->whereIn('nd.id', static fn ($sub) => $sub->select('ndvt.nguoi_dung_id')
                ->from('nguoi_dung_vai_tro ndvt')
                ->join('vai_tro vt', 'vt.id = ndvt.vai_tro_id')
                ->where('vt.ten_vai_tro', $tenVaiTro));
        }
        if ($tuKhoa !== null) {
            $builder->groupStart()
                ->like('nd.ten_dang_nhap', $tuKhoa)
                ->orLike('nd.ho_ten', $tuKhoa)
                ->orLike('nd.email', $tuKhoa)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $rows = $builder
            ->select('nd.id, nd.ten_dang_nhap, nd.ho_ten, nd.email, nd.so_dien_thoai, nd.bi_khoa, nd.created_at')
            ->select('(SELECT GROUP_CONCAT(vt2.ten_vai_tro ORDER BY vt2.id) FROM nguoi_dung_vai_tro ndvt2 JOIN vai_tro vt2 ON vt2.id = ndvt2.vai_tro_id WHERE ndvt2.nguoi_dung_id = nd.id) AS vai_tro', false)
            ->orderBy('nd.id', 'ASC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'size' => $size];
    }

    /** Doc va khoa dong nguoi dung trong giao dich */
    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT id, ten_dang_nhap, ho_ten, email, so_dien_thoai, bi_khoa FROM nguoi_dung WHERE id = ? FOR UPDATE',
            [$id]
        )->getRowArray();

        return $row ?: null;
    }

    public function datKhoa(int $id, bool $khoa): bool
    {
        return $this->update($id, ['bi_khoa' => $khoa ? 1 : 0]);
    }

    /** Thay toan bo vai tro cua nguoi dung bang mot vai tro (thao tac nam trong giao dich cua Service) */
    public function thayVaiTro(int $nguoiDungId, string $tenVaiTro): void
    {
        $this->db->table('nguoi_dung_vai_tro')->where('nguoi_dung_id', $nguoiDungId)->delete();
        $this->ganVaiTro($nguoiDungId, $tenVaiTro);
    }

    public function existsEmailKhac(string $email, int $boQuaId): bool
    {
        return $this->db->table('nguoi_dung')->where('email', $email)->where('id !=', $boQuaId)->countAllResults() > 0;
    }
}
