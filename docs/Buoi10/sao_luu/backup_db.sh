#!/usr/bin/env bash
# backup_db.sh - Sao lưu DỰ PHÒNG CSDL quan_ly_do_an trên máy cục bộ (MariaDB/MySQL, Linux/macOS)
# Bản nộp chính (thành phần 09 Phụ lục 08) là bản kết xuất TRỰC TUYẾN từ phpMyAdmin của InfinityFree;
# tệp này chỉ tạo bản dự phòng cục bộ.
# Tạo: tệp .sql kết xuất + .sha256 + tệp đếm số bản ghi gốc, trong thư mục sao_luu_ket_xuat.
# Cấu hình bằng biến môi trường (tuỳ chọn):
#   DB_USER (mặc định root)   DB_PASS (mặc định rỗng)   DB_PORT (3306)   MYSQL_BIN (thư mục chứa mysql, vd /opt/lampp/bin)
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
DB_NAME="quan_ly_do_an"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_PORT="${DB_PORT:-3306}"
BIN="${MYSQL_BIN:+$MYSQL_BIN/}"
[ -n "$DB_PASS" ] && export MYSQL_PWD="$DB_PASS"
KET_NOI=(-h127.0.0.1 -P"$DB_PORT" -u"$DB_USER")
OUT_DIR="$HERE/sao_luu_ket_xuat"
mkdir -p "$OUT_DIR"
TS="$(date +%Y%m%d_%H%M%S)"
FILE="$OUT_DIR/${DB_NAME}_cuc_bo_${TS}.sql"

echo "[1/4] Kết xuất CSDL $DB_NAME ..."
"${BIN}mysqldump" "${KET_NOI[@]}" --default-character-set=utf8mb4 \
  --single-transaction --routines --triggers --events \
  --result-file="$FILE" "$DB_NAME"

echo "[2/4] Kiểm tra tệp đã đầy đủ (dòng cuối 'Dump completed') ..."
tail -n 3 "$FILE" | grep "Dump completed" > /dev/null || { echo "[LỖI] Tệp kết xuất bị cắt ngang."; exit 1; }

echo "[3/4] Tính SHA-256 ..."
( cd "$OUT_DIR" && { sha256sum "$(basename "$FILE")" 2>/dev/null || shasum -a 256 "$(basename "$FILE")"; } > "$(basename "$FILE").sha256.txt" )

echo "[4/4] Đếm số bản ghi CSDL gốc lúc sao lưu ..."
"${BIN}mysql" "${KET_NOI[@]}" --default-character-set=utf8mb4 --table "$DB_NAME" < "$HERE/dem_ban_ghi.sql" > "$FILE.so_ban_ghi_goc.txt"

echo
echo "=== XONG ==="
echo "Tệp sao lưu : $FILE"
echo "SHA-256     : $FILE.sha256.txt"
echo "Số bản ghi  : $FILE.so_ban_ghi_goc.txt"
cat "$FILE.so_ban_ghi_goc.txt"
echo
echo "Bước tiếp theo: ./restore_test.sh \"$FILE\" cuc_bo"
