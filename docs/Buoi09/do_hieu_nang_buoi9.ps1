# =====================================================================
# do_hieu_nang_buoi9.ps1 | V2 - Buoi 9: do thoi gian phan hoi cua 5 diem cuoi API
# Moi diem cuoi: 1 lan lam nong (khong tinh) + 50 lan goi bang curl.exe, lay TRUNG VI.
#
#   .\do_hieu_nang_buoi9.ps1 -GiaiDoan TRUOC -MatKhau '<mat khau tai khoan thu>'
#   .\do_hieu_nang_buoi9.ps1 -GiaiDoan SAU   -MatKhau '<mat khau tai khoan thu>'
#   .\do_hieu_nang_buoi9.ps1 -SoSanh
#
# Ket qua: ket_qua_do_buoi9.csv (trung vi) va bang_do_hieu_nang_buoi9.md (bang TRUOC / SAU).
# =====================================================================
param(
    [ValidateSet('TRUOC', 'SAU')]
    [string]$GiaiDoan,
    [switch]$SoSanh,
    [string]$MatKhau,
    # Dung 127.0.0.1, khong dung localhost (Windows thu IPv6 truoc, moi lan goi cham them ~200 ms)
    [string]$GocApi = 'http://127.0.0.1/api/v1',
    [string]$MySql = 'C:\xampp\mysql\bin\mysql.exe',
    [string]$Csdl = 'quan_ly_do_an',
    [int]$CongCsdl = 3306
)

$ErrorActionPreference = 'Stop'
$VanHoa = [Globalization.CultureInfo]::InvariantCulture
$TepKetQua = Join-Path $PSScriptRoot 'ket_qua_do_buoi9.csv'
$TepBang = Join-Path $PSScriptRoot 'bang_do_hieu_nang_buoi9.md'

$DiemCuoi = @(
    @{ Ma = 'D1'; Duong = '/de-tai?lop_hoc_phan_id=1&trang_thai=in_progress&page=1&size=20'; TaiKhoan = 'thukykhoa'; Bang = 'de_tai'
       BienPhap = 'Chi muc (lop_hoc_phan_id, trang_thai, created_at): bo sap xep ca tap ket qua' },
    @{ Ma = 'D2'; Duong = '/public/de-tai?page=1'; TaiKhoan = ''; Bang = 'de_tai'
       BienPhap = 'Chi muc (created_at): doc nguoc chi muc thay cho quet toan bang' },
    @{ Ma = 'D3'; Duong = '/thong-ke/tien-do-nop'; TaiKhoan = 'thukykhoa'; Bang = 'bao_cao_moc'
       BienPhap = 'Chi muc phu (de_tai_id, moc_thoi_gian_id, created_at, id): chi doc chi muc' },
    @{ Ma = 'D4'; Duong = '/nghiem-thu/ho-so?page=1&size=20'; TaiKhoan = 'hd_pham_van_d'; Bang = 'de_tai'
       BienPhap = 'V3: gom truy van lap (42 -> 4 truy van) + bo nho dem 10 giay' },
    @{ Ma = 'D5'; Duong = '/nhat-ky-he-thong?hanh_dong=APPROVE_TOPIC'; TaiKhoan = 'thukykhoa'; Bang = 'nhat_ky_he_thong'
       BienPhap = 'Chi muc (hanh_dong, created_at): chi doc dong cua hanh dong can loc' }
)

function Get-TrungVi([double[]]$ds) {
    $s = $ds | Sort-Object
    return ($s[$s.Count / 2 - 1] + $s[$s.Count / 2]) / 2
}

if ($SoSanh) {
    $ds = Import-Csv $TepKetQua
    $moiNhat = @{}
    foreach ($d in $ds) { $moiNhat["$($d.giai_doan)|$($d.ma)"] = $d }
    $dong = @('| Ma | Diem cuoi | So ban ghi | Truoc (ms) | Sau (ms) | Bien phap ap dung | Cai thien |', '|---|---|---:|---:|---:|---|---:|')
    foreach ($d in $DiemCuoi) {
        $t = [double]::Parse($moiNhat["TRUOC|$($d.Ma)"].trung_vi_ms, $VanHoa)
        $s = [double]::Parse($moiNhat["SAU|$($d.Ma)"].trung_vi_ms, $VanHoa)
        $dong += '| {0} | `GET {1}` | {2} | {3:N1} | {4:N1} | {5} | {6:N1}% |' -f $d.Ma, $d.Duong, $moiNhat["SAU|$($d.Ma)"].so_ban_ghi, $t, $s, $d.BienPhap, ((1 - $s / $t) * 100)
    }
    $dong += ''
    $dong += 'Trung vi cua 50 lan goi moi diem cuoi (curl.exe, bo 1 lan lam nong); cung may, cung du lieu, OPcache bat.'
    [IO.File]::WriteAllLines($TepBang, $dong, (New-Object Text.UTF8Encoding $true))
    $dong | Write-Host
    return
}

if (-not $GiaiDoan -or -not $MatKhau) { throw 'Can -GiaiDoan TRUOC|SAU va -MatKhau (hoac chi -SoSanh)' }

# Dang nhap hai tai khoan, giu cookie phien trong tep
$jar = @{}
foreach ($ten in @('thukykhoa', 'hd_pham_van_d')) {
    $jar[$ten] = Join-Path $env:TEMP "dt13_$ten.jar"
    $than = Join-Path $env:TEMP 'dt13_dang_nhap.json'
    [IO.File]::WriteAllText($than, (@{ ten_dang_nhap = $ten; mat_khau = $MatKhau } | ConvertTo-Json -Compress))
    $ma = & curl.exe -s -c $jar[$ten] -H 'Content-Type: application/json' --data-binary "@$than" -o NUL -w '%{http_code}' "$GocApi/auth/dang-nhap"
    Remove-Item $than
    if ($ma -ne '200') { throw "Dang nhap $ten that bai (ma $ma)" }
}

$ketQua = @()
foreach ($d in $DiemCuoi) {
    $thamSo = @('-s', '-o', 'NUL', '-w', '%{http_code} %{time_total}')
    if ($d.TaiKhoan) { $thamSo += @('-b', $jar[$d.TaiKhoan]) }
    $mau = @()
    for ($i = 0; $i -le 50; $i++) {
        $kq = (& curl.exe @thamSo "$GocApi$($d.Duong)") -split ' '
        if ($kq[0] -ne '200') { throw "GET $($d.Duong) tra ma $($kq[0])" }
        if ($i -gt 0) { $mau += [double]::Parse($kq[1], $VanHoa) * 1000 }
    }
    $tv = Get-TrungVi $mau
    $soDong = "$(& $MySql '--host=127.0.0.1' "--port=$CongCsdl" '--user=root' '--skip-column-names' "--execute=SELECT COUNT(*) FROM $($d.Bang)" $Csdl)".Trim()
    Write-Host ('[{0}] {1} GET {2}: trung vi {3:N1} ms' -f $GiaiDoan, $d.Ma, $d.Duong, $tv)
    $ketQua += [pscustomobject]@{ giai_doan = $GiaiDoan; ma = $d.Ma; so_ban_ghi = $soDong; trung_vi_ms = $tv.ToString('0.0', $VanHoa); thoi_diem = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss') }
}
$ketQua | Export-Csv $TepKetQua -NoTypeInformation -Append -Encoding UTF8
$jar.Values | Remove-Item
Write-Host "Da ghi $TepKetQua"
