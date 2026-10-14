document.getElementById('form-dang-nhap').addEventListener('submit', async (e) => {
    e.preventDefault();
    const thongBao = document.getElementById('thong-bao');
    const nut = document.getElementById('nut-dang-nhap');
    const tenDangNhap = document.getElementById('ten_dang_nhap').value.trim();
    const matKhau = document.getElementById('mat_khau').value;

    thongBao.className = 'loi';
    thongBao.textContent = '';
    if (!tenDangNhap || !matKhau) {
        thongBao.textContent = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
        return;
    }

    nut.disabled = true;
    nut.textContent = 'Đang đăng nhập...';
    try {
        const r = await fetch('/api/v1/auth/dang-nhap', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ten_dang_nhap: tenDangNhap, mat_khau: matKhau }),
        });
        if (r.ok) {
            window.location.href = '/trang-chu';
            return;
        }
        if (r.status === 429) {
            const phut = Math.max(1, Math.ceil(Number(r.headers.get('Retry-After') || 900) / 60));
            thongBao.textContent = 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau ' + phut + ' phút.';
        } else {
            thongBao.textContent = r.status === 401
                ? 'Sai tên đăng nhập hoặc mật khẩu.'
                : 'Không đăng nhập được (mã ' + r.status + '). Vui lòng thử lại.';
        }
    } catch (err) {
        thongBao.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
    }
    nut.disabled = false;
    nut.textContent = 'Đăng nhập';
});

// ---------- Quen mat khau (F1.3): gui yeu cau, nhap ma thu ky khoa cap ----------
const formDangNhap = document.getElementById('form-dang-nhap');
const khoiQuen = document.getElementById('khoi-quen');

function hienQuen(hien) {
    formDangNhap.hidden = hien;
    khoiQuen.hidden = !hien;
    document.getElementById(hien ? 'email_quen' : 'ten_dang_nhap').focus();
}
document.getElementById('mo-quen').addEventListener('click', () => hienQuen(true));
document.getElementById('ve-dang-nhap').addEventListener('click', () => hienQuen(false));

async function guiJson(duongDan, than) {
    const r = await fetch(duongDan, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(than),
    });
    let j = null;
    try { j = await r.json(); } catch (err) { /* than rong */ }
    return { r, j };
}

document.getElementById('form-yeu-cau').addEventListener('submit', async (e) => {
    e.preventDefault();
    const tb = document.getElementById('tb-yeu-cau');
    const nut = document.getElementById('nut-yeu-cau');
    const email = document.getElementById('email_quen').value.trim();
    tb.className = 'loi';
    tb.textContent = '';
    if (!email) { tb.textContent = 'Vui lòng nhập email.'; return; }

    nut.disabled = true;
    try {
        const { r, j } = await guiJson('/api/v1/auth/quen-mat-khau', { email });
        if (r.ok) {
            tb.className = 'loi dat';
            tb.textContent = j.data.thong_bao;
        } else {
            tb.textContent = r.status === 422 ? 'Email không hợp lệ.' : 'Không gửi được yêu cầu (mã ' + r.status + ').';
        }
    } catch (err) {
        tb.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
    }
    nut.disabled = false;
});

document.getElementById('form-dat-lai').addEventListener('submit', async (e) => {
    e.preventDefault();
    const tb = document.getElementById('tb-dat-lai');
    const nut = document.getElementById('nut-dat-lai');
    const token = document.getElementById('ma_dat_lai').value.trim();
    const matKhauMoi = document.getElementById('mat_khau_moi').value;
    const nhapLai = document.getElementById('mat_khau_moi_2').value;
    tb.className = 'loi';
    tb.textContent = '';
    if (!token) { tb.textContent = 'Vui lòng nhập mã đặt lại.'; return; }
    if (matKhauMoi.length < 8 || matKhauMoi.length > 72) { tb.textContent = 'Mật khẩu mới từ 8 đến 72 ký tự.'; return; }
    if (matKhauMoi !== nhapLai) { tb.textContent = 'Hai lần nhập mật khẩu mới không khớp.'; return; }

    nut.disabled = true;
    try {
        const { r } = await guiJson('/api/v1/auth/dat-lai-mat-khau', { token, mat_khau_moi: matKhauMoi });
        if (r.ok) {
            e.target.reset();
            hienQuen(false);
            const tbDangNhap = document.getElementById('thong-bao');
            tbDangNhap.className = 'loi dat';
            tbDangNhap.textContent = 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.';
        } else if (r.status === 429) {
            const phut = Math.max(1, Math.ceil(Number(r.headers.get('Retry-After') || 900) / 60));
            tb.textContent = 'Nhập sai mã quá nhiều lần. Vui lòng thử lại sau ' + phut + ' phút.';
        } else {
            tb.textContent = r.status === 422 ? 'Mã đặt lại không đúng hoặc đã hết hạn.' : 'Không đặt lại được mật khẩu (mã ' + r.status + ').';
        }
    } catch (err) {
        tb.textContent = 'Không kết nối được máy chủ. Vui lòng thử lại.';
    }
    nut.disabled = false;
});
