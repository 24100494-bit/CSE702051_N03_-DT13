#!/usr/bin/env bash
# restore_test.sh - Phục hồi tệp sao lưu vào CSDL THỬ và chạy kiểm chứng (Linux/macOS)
# Chỉ thao tác trên quan_ly_do_an_restore_test, KHÔNG động vào CSDL gốc quan_ly_do_an.
# Cách chạy:
#   ./restore_test.sh sao_luu_ket_xuat/dump-truc-tuyen.sql      -> bản kết xuất từ phpMyAdmin InfinityFree (bản nộp chính)
#   ./restore_test.sh duong_dan/tep.sql cuc_bo                  -> bản dự phòng cục bộ, so sánh thêm với quan_ly_do_an
#   ./restore_test.sh                                           -> tệp .sql mới nhất trong sao_luu_ket_xuat
# Biến môi trường tuỳ chọn: DB_USER (root), DB_PASS (rỗng), DB_PORT (3306), MYSQL_BIN (vd /opt/lampp/bin)
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
TEST_DB="quan_ly_do_an_restore_test"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_PORT="${DB_PORT:-3306}"
BIN="${MYSQL_BIN:+$MYSQL_BIN/}"
[ -n "$DB_PASS" ] && export MYSQL_PWD="$DB_PASS"
OUT_DIR="$HERE/sao_luu_ket_xuat"
KET_NOI=(-h127.0.0.1 -P"$DB_PORT" -u"$DB_USER")

# Chốt an toàn: không bao giờ cho TEST_DB trùng CSDL thật
[ "$TEST_DB" != "quan_ly_do_an" ] || { echo "[LỖI] TEST_DB không được là quan_ly_do_an."; exit 1; }

BACKUP_FILE="${1:-}"
if [ -z "$BACKUP_FILE" ]; then
  BACKUP_FILE="$(ls -1t "$OUT_DIR"/*.sql 2>/dev/null | head -n 1 || true)"
fi
[ -n "$BACKUP_FILE" ] && [ -f "$BACKUP_FILE" ] || { echo "[LỖI] Không có tệp sao lưu. Đặt tệp kết xuất vào sao_luu_ket_xuat hoặc truyền đường dẫn."; exit 1; }
# Tệp xuất có USE / CREATE DATABASE sẽ nạp vào CSDL khác thay vì CSDL thử
if grep -qiE '^(USE |CREATE DATABASE)' "$BACKUP_FILE"; then
  echo "[LỖI] Tệp có lệnh USE hoặc CREATE DATABASE. Xuất lại từ phpMyAdmin, bỏ chọn \"Add CREATE DATABASE / USE statement\"."; exit 1
fi

echo "Tệp phục hồi : $BACKUP_FILE"
echo "CSDL thử     : $TEST_DB"
echo

echo "[1/3] Tạo lại CSDL thử (xoá bản cũ nếu có) ..."
"${BIN}mysql" "${KET_NOI[@]}" -e "DROP DATABASE IF EXISTS $TEST_DB; CREATE DATABASE $TEST_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "[2/3] Nạp tệp sao lưu vào $TEST_DB ..."
"${BIN}mysql" "${KET_NOI[@]}" --default-character-set=utf8mb4 "$TEST_DB" < "$BACKUP_FILE"

# phpMyAdmin 4.9 trên InfinityFree không xuất ràng buộc CHECK: CSDL vừa nạp chưa có CHECK nào thì bổ sung từ bo_sung_check.sql
SO_CHECK="$("${BIN}mysql" "${KET_NOI[@]}" -N "$TEST_DB" -e "SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()")"
if [ "$SO_CHECK" = "0" ]; then
  echo "[2b] Tệp xuất không có ràng buộc CHECK (phpMyAdmin 4.9) - bổ sung 11 CHECK từ bo_sung_check.sql ..."
  "${BIN}mysql" "${KET_NOI[@]}" --default-character-set=utf8mb4 "$TEST_DB" < "$HERE/bo_sung_check.sql"
fi

echo "[3/3] Chạy kiểm chứng (verify_restore.sql) ..."
mkdir -p "$OUT_DIR"
RESULT="$OUT_DIR/ket_qua_kiem_chung.txt"
"${BIN}mysql" "${KET_NOI[@]}" --default-character-set=utf8mb4 --table "$TEST_DB" < "$HERE/verify_restore.sql" > "$RESULT"
if [ "${2:-}" = "cuc_bo" ]; then
  "${BIN}mysql" "${KET_NOI[@]}" --default-character-set=utf8mb4 --table < "$HERE/so_sanh_voi_goc_cuc_bo.sql" >> "$RESULT"
fi

echo
cat "$RESULT"
echo
echo "=== XONG === Kết quả lưu tại: $RESULT"
echo "Đọc kết quả: mục 2 toàn DAT; mục 3, 4 toàn số 0; mục 5: bản web toàn KHONG (InfinityFree không cho tạo trigger), bản cục bộ toàn CO;"
echo "mục 1 phải trùng với ảnh chụp dem_ban_ghi.sql trên CSDL gốc. Chụp màn hình = Ảnh 33."
