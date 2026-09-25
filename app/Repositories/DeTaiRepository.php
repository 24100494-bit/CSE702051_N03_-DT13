<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * TANG TRUY CAP DU LIEU (Repository)
 * CHI duoc: truy van tham so hoa, tra ve mang du lieu tho.
 * KHONG duoc: chua dieu kien nghiep vu, kiem tra quyen.
 */
class DeTaiRepository
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table('de_tai')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function findAllPaginated(int $page, int $size, ?string $trangThai = null): array
    {
        $builder = $this->db->table('de_tai');

        if ($trangThai !== null) {
            $builder->where('trang_thai', $trangThai);
        }

        $total = $builder->countAllResults(false);

        $rows = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($size, ($page - 1) * $size)
            ->get()
            ->getResultArray();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'size' => $size];
    }
}
