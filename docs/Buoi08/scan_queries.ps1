# scan_queries.ps1 | V2 - Ra soat truy van: tim cho ghep chuoi du lieu dau vao vao cau SQL
# Dung:  powershell -ExecutionPolicy Bypass -File .\scan_queries.ps1 . > ket_qua_quet.txt
# Chi doc, khong sua file. Ket qua la DANH SACH NGHI VAN, moi dong phai duoc nguoi doc xac nhan.

param([string]$Root = ".")

$files = Get-ChildItem -Path $Root -Recurse -File -Include *.php,*.inc,*.sql |
    Where-Object { $_.FullName -notmatch '\\(vendor|\.git|node_modules)\\' }

function Section($title) { "`n==== $title ====" }

function Scan($pattern) {
    $files | Select-String -Pattern $pattern -CaseSensitive:$false | ForEach-Object {
        $rel = Resolve-Path -Relative $_.Path
        "{0}:{1}: {2}" -f $rel, $_.LineNumber, $_.Line.Trim()
    }
}

Section 'A. Du lieu dau vao (_GET/_POST/_REQUEST/_COOKIE/_SERVER/_FILES) nam cung dong voi cau SQL'
Scan '(SELECT|INSERT|UPDATE|DELETE|WHERE|ORDER BY|LIMIT|LIKE).*\$_(GET|POST|REQUEST|COOKIE|SERVER|FILES)'

Section 'B. Bien PHP chen thang trong chuoi SQL dung nhay kep ("... $bien ..." hoac {$bien})'
Scan '"[^"]*(SELECT|INSERT INTO|UPDATE|DELETE FROM)[^"]*(\$[a-zA-Z_]|\{\$)'

Section 'C. Ghep chuoi bang dau cham (.) hoac sprintf/implode quanh cau SQL'
Scan '(SELECT|INSERT INTO|UPDATE|DELETE FROM|WHERE|ORDER BY|LIMIT)[^;]*["'']\s*\.\s*\$'
Scan 'sprintf\s*\([^)]*(SELECT|INSERT|UPDATE|DELETE)'
Scan 'implode\s*\([^)]*\).*(IN\s*\(|WHERE)'

Section 'D. Goi query()/exec()/mysqli_query() truyen thang chuoi hoac bien (khong prepare)'
Scan '(->|::)(query|exec|multi_query|real_query)\s*\('
Scan '\bmysqli_(query|multi_query|real_query)\s*\('
Scan '\bmysql_query\s*\('

Section 'E. Chi escape thu cong (khong du an toan, nen chuyen sang prepared statement)'
Scan 'real_escape_string|mysqli_real_escape_string|addslashes|->quote\s*\('

Section 'F. ORDER BY / LIMIT / ten cot / ten bang lay tu bien (prepared statement KHONG che duoc, can whitelist)'
Scan '(ORDER BY|GROUP BY|LIMIT|OFFSET)\s+["''.]*\s*[{$]'
Scan '(FROM|JOIN|INTO|UPDATE)\s+["''.]*\s*\$[a-zA-Z_]'

Section 'G. Co dung prepared statement (doi chieu: cac cho nay thuong DAT, van nen xem lai tham so)'
Scan '(->|::)prepare\s*\('
Scan 'bind_param|bindParam|bindValue|execute\s*\(\s*\['

Section 'H. Thong ke so dong chua tu khoa SQL theo tep'
$files | ForEach-Object {
    $n = (Select-String -Path $_.FullName -Pattern 'SELECT|INSERT|UPDATE|DELETE' -CaseSensitive:$false | Measure-Object).Count
    if ($n -gt 0) { "{0}: {1}" -f (Resolve-Path -Relative $_.FullName), $n }
}

Section 'I. Query Builder CodeIgniter: cot sap xep tu bien, tat escape (false), like/where nhan bien, query() nhan bien'
Scan '->orderBy\(\s*\$'
Scan '->(select|where|orWhere|having|groupBy|join)\(.*,\s*(null\s*,\s*)?false\s*\)'
Scan '->(like|orLike|notLike|orNotLike)\(\s*[''"][^''"]+[''"]\s*,\s*\$'
Scan '->query\(\s*\$'
