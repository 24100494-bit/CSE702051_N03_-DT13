document.getElementById('form-dang-nhap').addEventListener('submit', async (e) => {
    e.preventDefault();
    const thongBao = document.getElementById('thong-bao');
    const nut = document.getElementById('nut-dang-nhap');
    const tenDangNhap = document.getElementById('ten_dang_nhap').value.trim();
    const matKhau = document.getElementById('mat_khau').value;

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
