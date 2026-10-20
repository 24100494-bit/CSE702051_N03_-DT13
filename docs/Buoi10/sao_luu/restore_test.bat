@echo off
rem ============================================================
rem restore_test.bat - Phuc hoi tep sao luu vao CSDL THU va chay kiem chung (XAMPP / MariaDB, Windows)
rem Chi thao tac tren CSDL quan_ly_do_an_restore_test, KHONG dong vao CSDL goc quan_ly_do_an.
rem Cach chay:
rem   restore_test.bat "sao_luu_ket_xuat\dump-truc-tuyen.sql"   -> ban ket xuat tu phpMyAdmin InfinityFree (ban nop chinh)
rem   restore_test.bat "duong_dan\tep.sql" cuc_bo               -> ban du phong cuc bo, so sanh them voi quan_ly_do_an
rem   restore_test.bat                                          -> tep .sql moi nhat trong sao_luu_ket_xuat
rem File nay chi dung chu khong dau de tranh loi ma hoa cua cmd.
rem ============================================================
setlocal EnableExtensions

rem ---- CAU HINH ----
if not defined MYSQL_BIN set "MYSQL_BIN=C:\xampp\mysql\bin"
if not defined DB_PORT set "DB_PORT=3306"
if not defined DB_USER set "DB_USER=root"
if not defined DB_PASS set "DB_PASS="
set "TEST_DB=quan_ly_do_an_restore_test"
rem ------------------

set "HERE=%~dp0"
set "OUT_DIR=%HERE%sao_luu_ket_xuat"
set "PASS_OPT="
if not "%DB_PASS%"=="" set "PASS_OPT=-p%DB_PASS%"
set "KET_NOI=-h127.0.0.1 -P%DB_PORT% -u%DB_USER% %PASS_OPT%"

rem Chot an toan: tuyet doi khong cho TEST_DB trung CSDL that
if /I "%TEST_DB%"=="quan_ly_do_an" (
  echo [LOI] TEST_DB khong duoc la quan_ly_do_an. Dung lai de bao ve du lieu that.
  exit /b 1
)

if not exist "%MYSQL_BIN%\mysql.exe" (
  echo [LOI] Khong tim thay mysql.exe tai "%MYSQL_BIN%". Sua MYSQL_BIN o dau file.
  exit /b 1
)

set "BACKUP_FILE=%~1"
if "%BACKUP_FILE%"=="" (
  for /f "delims=" %%F in ('dir /b /o-d "%OUT_DIR%\*.sql" 2^>nul') do (
    set "BACKUP_FILE=%OUT_DIR%\%%F"
    goto :co_tep
  )
)
:co_tep
if "%BACKUP_FILE%"=="" (
  echo [LOI] Khong co tep sao luu nao. Dat tep ket xuat vao sao_luu_ket_xuat hoac truyen duong dan.
  exit /b 1
)
if not exist "%BACKUP_FILE%" (
  echo [LOI] Khong tim thay tep: %BACKUP_FILE%
  exit /b 1
)
rem Doi sang duong dan day du: phia duoi pushd vao thu muc MySQL nen duong dan tuong doi se khong con dung
for %%F in ("%BACKUP_FILE%") do set "BACKUP_FILE=%%~fF"

rem Tep xuat co lenh CREATE DATABASE / USE se nap vao CSDL khac thay vi CSDL thu
findstr /R /I /C:"^USE " /C:"^CREATE DATABASE" "%BACKUP_FILE%" >nul
if not errorlevel 1 (
  echo [LOI] Tep co lenh USE hoac CREATE DATABASE. Xuat lai tu phpMyAdmin, bo chon "Add CREATE DATABASE / USE statement".
  exit /b 1
)

echo Tep phuc hoi : %BACKUP_FILE%
echo CSDL thu     : %TEST_DB%
echo.

pushd "%MYSQL_BIN%"

echo [1/3] Tao lai CSDL thu (xoa ban cu neu co) ...
mysql.exe %KET_NOI% -e "DROP DATABASE IF EXISTS %TEST_DB%; CREATE DATABASE %TEST_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :loi

echo [2/3] Nap tep sao luu vao %TEST_DB% ...
mysql.exe %KET_NOI% --default-character-set=utf8mb4 %TEST_DB% < "%BACKUP_FILE%"
if errorlevel 1 goto :loi

rem Chan truong hop nap that bai ma khong bao loi (CSDL thu van rong)
set "SO_BANG="
for /f %%c in ('mysql.exe %KET_NOI% -N %TEST_DB% -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA LIKE DATABASE()"') do set "SO_BANG=%%c"
if not "%SO_BANG%"=="13" (
  echo [LOI] CSDL thu co %SO_BANG% bang sau khi nap, can 13. Kiem tra lai tep sao luu.
  goto :loi
)

rem phpMyAdmin 4.9 tren InfinityFree khong xuat rang buoc CHECK: CSDL vua nap chua co CHECK nao thi bo sung tu bo_sung_check.sql
set "SO_CHECK="
for /f %%c in ('mysql.exe %KET_NOI% -N %TEST_DB% -e "SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA LIKE DATABASE()"') do set "SO_CHECK=%%c"
if "%SO_CHECK%"=="0" (
  echo [2b] Tep xuat khong co rang buoc CHECK ^(phpMyAdmin 4.9^) - bo sung 11 CHECK tu bo_sung_check.sql ...
  mysql.exe %KET_NOI% --default-character-set=utf8mb4 %TEST_DB% < "%HERE%bo_sung_check.sql"
  if errorlevel 1 goto :loi
)

echo [3/3] Chay kiem chung (verify_restore.sql) ...
set "RESULT=%OUT_DIR%\ket_qua_kiem_chung.txt"
if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"
mysql.exe %KET_NOI% --default-character-set=utf8mb4 --table %TEST_DB% < "%HERE%verify_restore.sql" > "%RESULT%"
if errorlevel 1 goto :loi
if /I "%~2"=="cuc_bo" (
  mysql.exe %KET_NOI% --default-character-set=utf8mb4 --table < "%HERE%so_sanh_voi_goc_cuc_bo.sql" >> "%RESULT%"
  if errorlevel 1 goto :loi
)
popd

echo.
type "%RESULT%"
echo.
echo === XONG === Ket qua luu tai: %RESULT%
echo Doc ket qua: muc 2 toan DAT; muc 3, 4 toan so 0; muc 5: ban web toan KHONG (InfinityFree khong cho tao trigger), ban cuc bo toan CO;
echo muc 1 phai trung voi anh chup dem_ban_ghi.sql tren CSDL goc. Chup man hinh = Anh 33.
endlocal
exit /b 0

:loi
popd
echo.
echo [LOI] Phuc hoi hoac kiem chung that bai. Xem thong bao loi o tren.
endlocal
exit /b 1
