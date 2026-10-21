@echo off
rem ============================================================
rem backup_db.bat - Sao luu DU PHONG CSDL quan_ly_do_an tren XAMPP (Windows)
rem Ban nop chinh (thanh phan 09 Phu luc 08) la ban ket xuat TRUC TUYEN tu phpMyAdmin cua InfinityFree;
rem tep nay chi tao ban du phong cuc bo (muc 8.3 tai lieu ky thuat: giu ban dung cuc bo de nghiem thu).
rem Tao: tep .sql ket xuat + tep .sha256.txt + tep dem so ban ghi goc, trong thu muc sao_luu_ket_xuat
rem Cach chay: bam dup chuot hoac mo cmd tai thu muc nay roi go backup_db.bat
rem File nay chi dung chu khong dau de tranh loi ma hoa cua cmd.
rem ============================================================
setlocal EnableExtensions

rem ---- CAU HINH (sua neu may khac) ----
if not defined MYSQL_BIN set "MYSQL_BIN=C:\xampp\mysql\bin"
if not defined DB_PORT set "DB_PORT=3306"
set "DB_NAME=quan_ly_do_an"
if not defined DB_USER set "DB_USER=root"
if not defined DB_PASS set "DB_PASS="
rem -------------------------------------

set "HERE=%~dp0"
set "OUT_DIR=%HERE%sao_luu_ket_xuat"
set "PASS_OPT="
if not "%DB_PASS%"=="" set "PASS_OPT=-p%DB_PASS%"
set "KET_NOI=-h127.0.0.1 -P%DB_PORT% -u%DB_USER% %PASS_OPT%"

if not exist "%MYSQL_BIN%\mysqldump.exe" (
  echo [LOI] Khong tim thay mysqldump.exe tai "%MYSQL_BIN%". Sua MYSQL_BIN o dau file.
  exit /b 1
)
if not exist "%OUT_DIR%" mkdir "%OUT_DIR%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "TS=%%i"
set "FILE=%OUT_DIR%\%DB_NAME%_cuc_bo_%TS%.sql"

echo [1/4] Ket xuat CSDL %DB_NAME% ...
pushd "%MYSQL_BIN%"
mysqldump.exe %KET_NOI% --default-character-set=utf8mb4 --single-transaction --routines --triggers --events --result-file="%FILE%" %DB_NAME%
if errorlevel 1 (
  popd
  echo [LOI] mysqldump that bai. Kiem tra MySQL da chay trong XAMPP va tai khoan/mat khau.
  exit /b 1
)

echo [2/4] Kiem tra tep ket xuat da day du (dong cuoi "Dump completed") ...
findstr /C:"Dump completed" "%FILE%" >nul
if errorlevel 1 (
  popd
  echo [LOI] Tep ket xuat bi cat ngang, khong co dong "Dump completed".
  exit /b 1
)

echo [3/4] Tinh SHA-256 ...
certutil -hashfile "%FILE%" SHA256 > "%FILE%.sha256.txt"

echo [4/4] Dem so ban ghi CSDL goc luc sao luu ...
mysql.exe %KET_NOI% --default-character-set=utf8mb4 --table %DB_NAME% < "%HERE%dem_ban_ghi.sql" > "%FILE%.so_ban_ghi_goc.txt"
if errorlevel 1 (
  popd
  echo [LOI] Khong dem duoc so ban ghi.
  exit /b 1
)
popd

echo.
echo === XONG ===
echo Tep sao luu : %FILE%
echo SHA-256     : %FILE%.sha256.txt
echo So ban ghi  : %FILE%.so_ban_ghi_goc.txt
type "%FILE%.so_ban_ghi_goc.txt"
echo.
echo Buoc tiep theo: restore_test.bat "%FILE%" cuc_bo
endlocal
