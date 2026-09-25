<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Muc 1 - quyen chuc nang: vai tro nao duoc goi chuc nang nao.
 * Lay tu sheet "Ma tran phan quyen" trong tep Ma tran phan quyen.xlsx (o nao khac "Khong" la duoc goi).
 * Cac o theo pham vi (Cua minh, Nhom phu trach...) van cho qua o day,
 * phan kiem tra tren dung ban ghi nam o tang Service (muc 2).
 * Chuc nang danh cho Khach (F1.1, F1.2, F1.3, F2.1, F7.1) khong can khai bao.
 */
class PhanQuyen extends BaseConfig
{
    /**
     * @var array<string, list<string>> ma chuc nang => danh sach vai tro duoc goi
     */
    public array $chucNang = [
        'F1.4'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Doi mat khau
        'F1.5'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Xem, cap nhat ho so ca nhan
        'F1.6'  => ['thu_ky_khoa'],                                  // Quan ly tai khoan, khoa/mo, doi vai tro
        'F2.2'  => ['sinh_vien'],                                    // De xuat de tai moi
        'F2.3'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Xem chi tiet de tai
        'F2.4'  => ['sinh_vien'],                                    // Cap nhat de tai khi con o trang thai de xuat
        'F2.5'  => ['sinh_vien'],                                    // Xoa de xuat chua duyet
        'F2.6'  => ['gvhd'],                                         // Duyet de tai
        'F2.7'  => ['gvhd'],                                         // Tu choi, yeu cau chinh sua de tai
        'F2.8'  => ['thu_ky_khoa'],                                  // Phan cong GVHD cho nhom
        'F2.9'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Tim kiem, loc, phan trang de tai
        'F2.10' => ['sinh_vien', 'thu_ky_khoa'],                     // Quan ly nhom sinh vien
        'F2.11' => ['sinh_vien', 'thu_ky_khoa'],                     // Them, xoa thanh vien nhom
        'F3.1'  => ['thu_ky_khoa'],                                  // Cau hinh moc thoi gian chung
        'F3.2'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Xem danh sach moc
        'F3.3'  => ['thu_ky_khoa'],                                  // Cap nhat moc
        'F3.4'  => ['sinh_vien'],                                    // Nop bao cao tien do theo moc
        'F3.5'  => ['sinh_vien'],                                    // Nop lai, ghi de khi moc con mo
        'F3.6'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa'],             // Xem chi tiet bao cao moc
        'F3.7'  => ['gvhd'],                                         // Duyet bao cao moc (Dat)
        'F3.8'  => ['gvhd'],                                         // Yeu cau bo sung bao cao moc
        'F3.9'  => [],                                               // Quet va danh dau qua han (noi bo)
        'F3.10' => ['sinh_vien', 'gvhd'],                            // Xem lich su trang thai de tai / moc
        'F4.1'  => ['hoi_dong'],                                     // Danh sach ho so du dieu kien nghiem thu
        'F4.2'  => ['hoi_dong'],                                     // Xem chi tiet ho so nghiem thu
        'F4.3'  => ['hoi_dong'],                                     // Cham diem nghiem thu
        'F4.4'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Xem diem nghiem thu da cham
        'F5.1'  => ['gvhd', 'thu_ky_khoa'],                          // Thong ke tien do toan khoa
        'F5.2'  => ['thu_ky_khoa'],                                  // Xuat bao cao thong ke CSV/Excel
        'F5.3'  => ['thu_ky_khoa'],                                  // Tra cuu nhat ky he thong
        'F6.1'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Danh sach thong bao cua toi
        'F6.2'  => ['sinh_vien', 'gvhd', 'thu_ky_khoa', 'hoi_dong'], // Danh dau thong bao da doc
        'F6.3'  => [],                                               // Tac vu nhac han (noi bo)
    ];
}
