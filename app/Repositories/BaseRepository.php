<?php

namespace App\Repositories;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Lop repository nen tang: CRUD dung chung cho cac bang co khoa chinh don gian la `id`.
 *
 * Moi gia tri dong deu di qua Query Builder cua CodeIgniter (tu dong bind/escape),
 * khong bao gio ghep chuoi gia tri dau vao vao cau SQL.
 * Ten bang, ten cot chi lay tu thuoc tinh noi bo cua class.
 */
abstract class BaseRepository
{
    protected ConnectionInterface $db;
    protected string $table;
    protected string $primaryKey = 'id';

    /**
     * Chi cac cot nay duoc ghi qua insert()/update(); key khac trong mang du lieu bi bo qua,
     * nen du Service lo truyen nguyen du lieu nguoi dung gui len cung khong ghi duoc cot la.
     *
     * @var list<string>
     */
    protected array $allowedFields = [];

    /** Ket noi da duoc dat mui gio phien (moi ket noi chi dat mot lan) */
    private static array $ketNoiDaDatMuiGio = [];

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->datMuiGioPhien();
    }

    /**
     * Dat mui gio phien CSDL trung voi mui gio ung dung (App::$appTimezone),
     * de NOW(), CURRENT_TIMESTAMP va cot TIMESTAMP cung gio voi PHP, khong phu thuoc gio may chu CSDL.
     */
    private function datMuiGioPhien(): void
    {
        $khoa = spl_object_id($this->db);

        if (isset(self::$ketNoiDaDatMuiGio[$khoa])) {
            return;
        }

        $this->db->query('SET time_zone = ?', [date('P')]);
        self::$ketNoiDaDatMuiGio[$khoa] = true;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->table($this->table)
            ->where($this->primaryKey, $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function findAll(): array
    {
        return $this->db->table($this->table)
            ->orderBy($this->primaryKey, 'DESC')
            ->get()
            ->getResultArray();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->db->table($this->table)->where($this->primaryKey, $id)->delete();
    }

    protected function insert(array $data): int
    {
        $this->db->table($this->table)->insert($this->locCot($data));

        return (int) $this->db->insertID();
    }

    /** Cap nhat mot phan cac cot cua ban ghi co id tuong ung */
    protected function update(int $id, array $data): bool
    {
        return (bool) $this->db->table($this->table)
            ->where($this->primaryKey, $id)
            ->update($this->locCot($data));
    }

    private function locCot(array $data): array
    {
        return array_intersect_key($data, array_flip($this->allowedFields));
    }

    /** Giao dich do tang Service mo/dong khi ghi nhieu bang */
    public function transBegin(): void
    {
        // Bat buoc nem loi khi mot cau lenh trong giao dich that bai, de Service bat duoc va hoan nguyen;
        // mac dinh CodeIgniter chi danh dau that bai ma khong nem, khien transCommit() ghi do dang
        $this->db->transException(true);
        $this->db->transBegin();
    }

    public function transCommit(): void
    {
        $this->db->transCommit();
    }

    public function transRollback(): void
    {
        $this->db->transRollback();
    }
}
