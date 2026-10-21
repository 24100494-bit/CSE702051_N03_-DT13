<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * TANG TRUY CAP DU LIEU (Repository) - kiem tra CSDL con tra loi cho diem cuoi tinh trang GET /api/v1/health.
 * Khong ke thua BaseRepository vi khong gan voi bang nao.
 */
class TinhTrangRepository
{
    public function __construct(private ConnectionInterface $db)
    {
    }

    /** Chay SELECT 1; mat ket noi thi ham query nem DatabaseException */
    public function csdlTraLoi(): bool
    {
        $row = $this->db->query('SELECT 1 AS ok')->getRowArray();

        return (int) ($row['ok'] ?? 0) === 1;
    }
}
