GOI FILE V3 - BUOI 09

Cac tep can chep vao dung duong dan trong project:
1. app/Services/NghiemThuService.php
2. app/Repositories/DiemNghiemThuRepository.php
3. app/Repositories/BaoCaoMocRepository.php
4. schema.sql (them chi muc cho database tao moi)
5. indexes_buoi09.sql (chay trong database hien co de bo sung chi muc, co the chay lai an toan)
6. tests/unit/NghiemThuServicePerformanceTest.php (tep moi)
7. docs/Buoi09/V3_DANH_MUC_DIEM_CUOI_DA_TOI_UU.md (tep moi)

Sau khi chep, tai thu muc goc project chay:
./vendor/bin/phpunit --bootstrap "$(pwd)/vendor/codeigniter4/framework/system/Test/bootstrap.php" tests/unit/NghiemThuServicePerformanceTest.php
./vendor/bin/phpunit --bootstrap "$(pwd)/vendor/codeigniter4/framework/system/Test/bootstrap.php" tests/unit

ZIP source ban gui khong kem vendor, nen chua the chay PHPUnit trong moi truong dong goi. Da kiem tra cu phap PHP bang php -l cho ba tep PHP duoc sua/them.

Luu y: can do 50 mau truoc/sau tren cung moi truong va dien so lieu that vao bang trong tai lieu danh muc. Khong su dung so lieu uoc do thay cho ket qua do thuc te.
