// Trang chu theo vai tro: goi cac API /api/v1 bang phien dang nhap hien tai.
// Du lieu tu may chu chi gan vao trang bang textContent / value, khong dung innerHTML (chong XSS).
(() => {
    'use strict';

    const API = '/api/v1';
    const goc = document.getElementById('ung-dung');
    const vaiTro = (goc.dataset.vaiTro || '').split(',').filter(Boolean);
    const toiId = Number(goc.dataset.id);
    const khoiVaiTro = document.getElementById('khoi-vai-tro');
    const tenNguoiDung = goc.dataset.ten || '';
    // Trang dang dung: cac ham khoi...() gan the moi vao day
    let khoi = khoiVaiTro;

    const TEN_TRANG_THAI = {
        pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Bị từ chối', in_progress: 'Đang thực hiện',
        completed: 'Đủ điều kiện nghiệm thu', accepted: 'Đã nghiệm thu', revision_requested: 'Cần bổ sung', overdue: 'Quá hạn',
    };
    const CO_BAO_CAO = ['approved', 'in_progress', 'completed', 'accepted'];
    const NGHIEM_THU = ['completed', 'accepted'];
    const TEN_VAI_TRO = { SINH_VIEN: 'Sinh viên', GVHD: 'GVHD', THU_KY_KHOA: 'Thư ký khoa', HOI_DONG: 'Hội đồng' };

    // ---------- Tien ich ----------
    async function goi(method, url, body) {
        const tuyChon = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body instanceof FormData) {
            tuyChon.body = body;
        } else if (body !== undefined) {
            tuyChon.headers['Content-Type'] = 'application/json';
            tuyChon.body = JSON.stringify(body);
        }
        let r;
        try {
            r = await fetch(API + url, tuyChon);
        } catch (e) {
            return { ok: false, status: 0, loi: 'Không kết nối được máy chủ' };
        }
        if (r.status === 401) {
            window.location.href = '/dang-nhap';
        }
        let json = null;
        try { json = await r.json(); } catch (e) { /* 204 khong co than */ }
        if (r.ok) {
            return { ok: true, status: r.status, data: json ? json.data : null };
        }
        const loi = json && json.error ? json.error : {};
        const chiTiet = (loi.details || []).map((d) => d.field + ': ' + d.issue).join('; ');
        const truong = (loi.details || []).flatMap((d) => String(d.field || '').split(',').map((t) => t.trim())).filter(Boolean);
        return { ok: false, status: r.status, truong, loi: (loi.message || ('Lỗi ' + r.status)) + (chiTiet ? ' (' + chiTiet + ')' : '') };
    }

    function tao(the, thuocTinh, ...con) {
        const e = document.createElement(the);
        Object.entries(thuocTinh || {}).forEach(([k, v]) => {
            if (k === 'class') e.className = v;
            else if (k === 'text') e.textContent = v;
            else if (k.startsWith('on')) e.addEventListener(k.slice(2), v);
            else if (k === 'style') e.style.cssText = v; // gan qua CSSOM, CSP khong cho thuoc tinh style
            else if (v !== false && v !== null && v !== undefined) e.setAttribute(k, v === true ? '' : v);
        });
        con.flat().forEach((c) => { if (c !== null && c !== undefined && c !== false) e.append(c instanceof Node ? c : String(c)); });
        return e;
    }

    const nhanTrangThai = (tt) => tao('span', { class: 'nhan nhan-' + tt, text: TEN_TRANG_THAI[tt] || tt });
    // Tai tep bao cao qua API co kiem quyen (tep luu ngoai thu muc web)
    const nutTaiTep = (baoCaoId, tenTep) => tao('a', {
        class: 'nut-tai', href: API + '/bao-cao-moc/' + baoCaoId + '/tep', download: '', text: 'Tải tệp', 'aria-label': 'Tải tệp ' + tenTep,
    });
    const thoiGian = (s) => (s ? String(s).slice(0, 16).replace('T', ' ') : '');

    let soOLoi = 0;
    function oThongBao() {
        return tao('p', { class: 'loi', role: 'alert', 'aria-live': 'assertive', id: 'o-loi-' + (++soOLoi) });
    }

    /**
     * Bo danh dau cu; neu may chu bao loi cac truong thi danh dau o nhap tuong ung (aria-invalid),
     * noi o do voi thong bao loi (aria-describedby) va dua tieu diem ve o sai dau tien
     */
    function danhDauLoi(nut, oLoi, truong) {
        const vung = oLoi.closest('section') || document;
        vung.querySelectorAll('[aria-invalid="true"]').forEach((o) => { o.removeAttribute('aria-invalid'); o.removeAttribute('aria-describedby'); });
        if (!truong || !truong.length) return;
        const nhom = nut.closest('form, .luoi-form, .hang, tr') || vung;
        let sai = truong.map((t) => nhom.querySelector('[name="' + t + '"]') || vung.querySelector('[name="' + t + '"]')).filter(Boolean);
        if (!sai.length) {
            // O nhap trong cung hang voi nut khong dat name: hang chi co mot o thi do la o sai
            const oNhap = [...nhom.querySelectorAll('input, select, textarea')];
            if (oNhap.length === 1) sai = oNhap;
        }
        sai.forEach((o) => { o.setAttribute('aria-invalid', 'true'); o.setAttribute('aria-describedby', oLoi.id); });
        if (sai[0]) sai[0].focus();
    }

    /** Chay thao tac, hien loi vao o, thanh cong thi goi lai ham tai lai */
    async function thucHien(nut, oLoi, hamGoi, taiLai) {
        nut.disabled = true;
        oLoi.textContent = '';
        danhDauLoi(nut, oLoi);
        const kq = await hamGoi();
        nut.disabled = false;
        if (!kq.ok) {
            oLoi.textContent = kq.loi;
            danhDauLoi(nut, oLoi, kq.truong);
            return kq;
        }
        if (taiLai) await taiLai();
        return kq;
    }

    function oNhap(nhan, ten, thuocTinh = {}) {
        return tao('label', {}, nhan, tao('input', Object.assign({ name: ten }, thuocTinh)));
    }

    // Khoi trung ten voi trang dang dung thi an tieu de khoi (van giu cho trinh doc man hinh)
    let tenTrangDangDung = '';
    function theMoi(tieuDe) {
        const the = tao('section', { class: 'the' }, tao('h2', { text: tieuDe, class: tieuDe === tenTrangDangDung ? 'an-chu' : false }));
        khoi.append(the);
        return the;
    }

    // ---------- Bieu tuong SVG (ve san, khong tai tu nguon ngoai vi CSP) ----------
    const DUONG_VE = {
        logo: 'M2 9l10-5 10 5-10 5zM6 11v5c3 2 9 2 12 0v-5M22 9v6',
        menu: 'M4 6h16M4 12h16M4 18h16',
        chuong: 'M6 16v-5a6 6 0 0 1 12 0v5l2 2H4zM10 21a2 2 0 0 0 4 0',
        'dang-xuat': 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10',
        'tong-quan': 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        'tim-kiem': 'M11 4a7 7 0 1 0 0 14a7 7 0 0 0 0-14zM20 20l-4-4',
        'de-tai': 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
        nhom: 'M9 11a3 3 0 1 0 0-6a3 3 0 0 0 0 6zM3 20a6 6 0 0 1 12 0M16 5.5a3 3 0 0 1 0 5.5M21 20a6 6 0 0 0-3.5-5.5',
        'nghiem-thu': 'M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0-18zM8 12l3 3 5-6',
        lich: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
        'tai-khoan': 'M12 12a4 4 0 1 0 0-8a4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
        'nhat-ky': 'M12 7v5l3 2M12 3a9 9 0 1 0 0 18a9 9 0 0 0 0-18z',
        'thong-ke': 'M4 20h16M7 16v-6M12 16V6M17 16v-4',
        'ho-so': 'M3 5h18v14H3zM8 9.5a2 2 0 1 0 0 4a2 2 0 0 0 0-4zM5.5 17a3 3 0 0 1 5 0M14 9h4M14 13h4',
        'mui-ten': 'M5 12h14M13 6l6 6-6 6',
        'bao-cao': 'M12 3v12M7 10l5 5 5-5M5 21h14',
        'canh-bao': 'M12 4l9 16H3zM12 10v4M12 17v.01',
    };
    function icon(ten) {
        return svg('svg', { class: 'icon', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.8', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false' },
            svg('path', { d: DUONG_VE[ten] || '' }));
    }
    const iconTron = (ten) => tao('span', { class: 'icon-tron', 'aria-hidden': 'true' }, icon(ten));

    // ---------- Thanh tien do cua de tai: 5 buoc tu de xuat den nghiem thu ----------
    const BUOC_DE_TAI = ['Đề xuất', 'Được duyệt', 'Đang thực hiện', 'Đủ điều kiện nghiệm thu', 'Đã nghiệm thu'];
    const BUOC_HIEN_TAI = { pending: 1, rejected: 1, approved: 2, in_progress: 2, completed: 4, accepted: 5 };
    function thanhTienDo(trangThai) {
        const hienTai = BUOC_HIEN_TAI[trangThai] ?? 0;
        return tao('ol', { class: 'tien-do', 'aria-label': 'Tiến độ đề tài: ' + (TEN_TRANG_THAI[trangThai] || trangThai) },
            BUOC_DE_TAI.map((ten, i) => {
                const loi = trangThai === 'rejected' && i === 1;
                const lop = loi ? 'loi-buoc' : (i < hienTai ? 'xong' : (i === hienTai ? 'hien-tai' : ''));
                const trangThaiBuoc = loi ? ' (bị từ chối)' : (i < hienTai ? ' (đã xong)' : (i === hienTai ? ' (đang ở bước này)' : ''));
                const tenBuoc = loi ? 'Bị từ chối' : (trangThai === 'pending' && i === 1 ? 'Chờ duyệt' : ten);
                return tao('li', { class: lop }, tenBuoc, trangThaiBuoc ? tao('span', { class: 'an-chu', text: trangThaiBuoc }) : null);
            }));
    }

    // ---------- Thong bao (moi vai tro) ----------
    async function taiThongBao() {
        const ds = document.getElementById('ds-thong-bao');
        const kq = await goi('GET', '/thong-bao?size=10');
        ds.replaceChildren();
        if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return null; }
        capNhatSoChuaDoc(Number(kq.data.chua_doc) || 0);
        if (!kq.data.items.length) { ds.append(tao('p', { class: 'phu', text: 'Chưa có thông báo.' })); return kq.data; }
        kq.data.items.forEach((tb) => {
            const oLoi = oThongBao();
            const nut = Number(tb.da_doc) ? null : tao('button', {
                type: 'button', class: 'nut-phu', text: 'Đã đọc',
                onclick: (e) => thucHien(e.target, oLoi, () => goi('PATCH', '/thong-bao/' + tb.id + '/da-doc'), taiThongBao),
            });
            ds.append(tao('div', { class: 'muc' + (Number(tb.da_doc) ? '' : ' chua-doc') },
                iconTron('chuong'),
                tao('h3', { text: tb.tieu_de }), tao('p', { class: 'phu', text: tb.noi_dung + ' · ' + thoiGian(tb.created_at) }),
                nut ? tao('div', { class: 'hang' }, nut) : null, oLoi));
        });
        return kq.data;
    }

    function capNhatSoChuaDoc(so) {
        document.getElementById('so-chua-doc').textContent = so ? so + ' chưa đọc' : '';
        const cham = document.getElementById('so-chuong');
        cham.textContent = so > 99 ? '99+' : String(so);
        cham.hidden = !so;
        document.getElementById('nut-chuong').setAttribute('aria-label', 'Thông báo' + (so ? ', ' + so + ' chưa đọc' : ''));
        const dem = document.querySelector('#menu a[href="#thong-bao"] .dem');
        if (dem) { dem.textContent = String(so); dem.hidden = !so; }
    }

    // ---------- Dung chung: bao cao cua mot de tai ----------
    async function hienBaoCao(vung, deTai, laNhomTruong, laGvhd) {
        vung.replaceChildren(tao('p', { class: 'phu', text: 'Đang tải báo cáo...' }));
        const [kqBc, kqMoc] = await Promise.all([
            goi('GET', '/de-tai/' + deTai.id + '/bao-cao-moc'),
            goi('GET', '/moc-thoi-gian?lop_hoc_phan_id=' + deTai.lop_hoc_phan_id),
        ]);
        vung.replaceChildren();
        if (!kqBc.ok) { vung.append(tao('p', { class: 'loi', text: kqBc.loi })); return; }
        const moc = kqMoc.ok ? kqMoc.data : [];
        const tenMoc = Object.fromEntries(moc.map((m) => [String(m.id), m.ten_moc]));
        const taiLai = () => hienBaoCao(vung, deTai, laNhomTruong, laGvhd);

        // Ban moi nhat cua tung moc (danh sach da sap moi nhat truoc)
        const moiNhat = {};
        kqBc.data.forEach((bc) => { if (!moiNhat[bc.moc_thoi_gian_id]) moiNhat[bc.moc_thoi_gian_id] = bc.id; });

        if (!kqBc.data.length) vung.append(tao('p', { class: 'phu', text: 'Chưa có báo cáo nào.' }));
        kqBc.data.forEach((bc) => {
            const oLoi = oThongBao();
            const hang = tao('div', { class: 'hang' });
            if (laGvhd && bc.trang_thai === 'pending') {
                const nhanXet = tao('input', { placeholder: 'Nhận xét', 'aria-label': 'Nhận xét báo cáo ' + bc.id });
                hang.append(tao('label', {}, 'Nhận xét', nhanXet),
                    tao('button', { type: 'button', text: 'Duyệt', onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/bao-cao-moc/' + bc.id + '/duyet', { nhan_xet_gv: nhanXet.value }), taiLai) }),
                    tao('button', { type: 'button', class: 'nut-nguy', text: 'Yêu cầu bổ sung', onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/bao-cao-moc/' + bc.id + '/yeu-cau-bo-sung', { nhan_xet_gv: nhanXet.value }), taiLai) }));
            }
            if (laNhomTruong && bc.trang_thai === 'revision_requested' && moiNhat[bc.moc_thoi_gian_id] === bc.id) {
                const tep = tao('input', { type: 'file', accept: '.pdf,.docx' });
                hang.append(tao('label', {}, 'Tệp nộp lại', tep), tao('button', {
                    type: 'button', text: 'Nộp lại',
                    onclick: (e) => thucHien(e.target, oLoi, () => {
                        const fd = new FormData();
                        if (tep.files[0]) fd.append('tep', tep.files[0]);
                        return goi('POST', '/bao-cao-moc/' + bc.id, fd);
                    }, taiLai),
                }));
            }
            vung.append(tao('div', { class: 'muc-con' },
                tao('div', { class: 'hang' }, tao('strong', { text: tenMoc[String(bc.moc_thoi_gian_id)] || ('Mốc ' + bc.moc_thoi_gian_id) }), nhanTrangThai(bc.trang_thai),
                    tao('span', { class: 'phu', text: bc.ten_tep_goc + ' · ' + thoiGian(bc.created_at) }), nutTaiTep(bc.id, bc.ten_tep_goc)),
                bc.nhan_xet_gv ? tao('p', { class: 'phu', text: 'Nhận xét: ' + bc.nhan_xet_gv }) : null,
                hang.childNodes.length ? hang : null, oLoi));
        });

        if (laNhomTruong && ['approved', 'in_progress'].includes(deTai.trang_thai)) {
            const oLoi = oThongBao();
            const chonMoc = tao('select', { name: 'moc_thoi_gian_id' },
                moc.map((m) => tao('option', { value: m.id, text: m.ten_moc + ' (hạn ' + thoiGian(m.han_nop) + ')' })));
            const tep = tao('input', { type: 'file', accept: '.pdf,.docx' });
            vung.append(tao('div', { class: 'luoi-form' },
                tao('label', {}, 'Mốc', chonMoc), tao('label', {}, 'Tệp (.pdf, .docx, tối đa 5 MB)', tep),
                tao('button', {
                    type: 'button', text: 'Nộp báo cáo',
                    onclick: (e) => thucHien(e.target, oLoi, () => {
                        const fd = new FormData();
                        fd.append('moc_thoi_gian_id', chonMoc.value);
                        if (tep.files[0]) fd.append('tep', tep.files[0]);
                        return goi('POST', '/de-tai/' + deTai.id + '/bao-cao-moc', fd);
                    }, taiLai),
                })), oLoi);
        }
    }

    // ---------- Dung chung: diem nghiem thu (F4.4) ----------
    async function hienDiem(vung, deTai) {
        const kq = await goi('GET', '/de-tai/' + deTai.id + '/diem-nghiem-thu');
        vung.replaceChildren();
        if (!kq.ok) { vung.append(tao('p', { class: 'loi', text: kq.loi })); return; }
        const d = kq.data;
        if (!d.chi_tiet.length) {
            vung.append(tao('p', { class: 'phu', text: d.so_luot_cham === null ? 'Hội đồng đang chấm, điểm công bố sau khi thư ký khoa chốt.' : 'Chưa có lượt chấm.' }));
            return;
        }
        vung.append(tao('div', { class: 'muc-con' },
            tao('p', {}, tao('strong', { text: 'Điểm trung bình: ' + (d.diem_trung_binh ?? '—') }),
                ' · ' + d.so_luot_cham + ' lượt chấm' + (d.cong_bo ? '' : ' (chưa chốt)')),
            tao('ul', {}, d.chi_tiet.map((c) => tao('li', { text: c.nguoi_cham + ': ' + c.diem + (c.nhan_xet ? ' – ' + c.nhan_xet : '') })))));
    }

    // ---------- K5: Tim kiem, loc, sap xep, phan trang de tai ----------
    async function khoiTimKiemDeTai() {
        const the = theMoi('Tìm kiếm, lọc và phân trang đề tài');
        let trang = 1;

        const tuKhoa = tao('input', { placeholder: 'Tên đề tài hoặc mô tả', 'aria-label': 'Từ khóa' });
        const trangThai = tao('select', { 'aria-label': 'Lọc trạng thái' },
            tao('option', { value: '', text: 'Tất cả trạng thái' }),
            tao('option', { value: 'pending', text: 'Chờ duyệt' }),
            tao('option', { value: 'approved', text: 'Đã duyệt' }),
            tao('option', { value: 'rejected', text: 'Bị từ chối' }),
            tao('option', { value: 'in_progress', text: 'Đang thực hiện' }),
            tao('option', { value: 'completed', text: 'Đủ điều kiện nghiệm thu' }),
            tao('option', { value: 'accepted', text: 'Đã nghiệm thu' }));
        const lopHocPhan = tao('input', { type: 'number', min: 1, placeholder: 'Mã lớp học phần', 'aria-label': 'Mã lớp học phần' });
        const sapXep = tao('select', { 'aria-label': 'Sắp xếp theo' },
            tao('option', { value: 'created_at', text: 'Ngày tạo' }),
            tao('option', { value: 'ten_de_tai', text: 'Tên đề tài' }),
            tao('option', { value: 'trang_thai', text: 'Trạng thái' }));
        const huong = tao('select', { 'aria-label': 'Hướng sắp xếp' },
            tao('option', { value: 'DESC', text: 'Giảm dần' }),
            tao('option', { value: 'ASC', text: 'Tăng dần' }));
        const nutTim = tao('button', { type: 'button', text: 'Tìm kiếm' });
        const nutXoa = tao('button', { type: 'button', class: 'nut-phu', text: 'Xóa bộ lọc' });
        const ds = tao('div');
        const phanTrang = tao('div', { class: 'hang' });
        const oThongTin = tao('p', { class: 'phu', 'aria-live': 'polite' });
        const oLoi = oThongBao();

        the.append(
            tao('div', { class: 'luoi-form' },
                tao('label', {}, 'Từ khóa', tuKhoa),
                tao('label', {}, 'Trạng thái', trangThai),
                tao('label', {}, 'Lớp học phần', lopHocPhan),
                tao('label', {}, 'Sắp xếp', sapXep),
                tao('label', {}, 'Hướng', huong)),
            tao('div', { class: 'hang' }, nutTim, nutXoa),
            oLoi, oThongTin, ds, phanTrang);

        async function taiDs() {
            const thamSo = new URLSearchParams({
                page: String(trang),
                size: '10',
                sap_xep: sapXep.value,
                huong: huong.value,
            });
            if (tuKhoa.value.trim()) thamSo.set('tu_khoa', tuKhoa.value.trim());
            if (trangThai.value) thamSo.set('trang_thai', trangThai.value);
            if (lopHocPhan.value) thamSo.set('lop_hoc_phan_id', lopHocPhan.value);

            ds.replaceChildren(tao('p', { class: 'phu', text: 'Đang tải...' }));
            phanTrang.replaceChildren();
            const kq = await goi('GET', '/de-tai?' + thamSo.toString());
            ds.replaceChildren();
            if (!kq.ok) {
                oLoi.textContent = kq.loi;
                oThongTin.textContent = '';
                return;
            }
            oLoi.textContent = '';
            oThongTin.textContent = Number(kq.data.total) ? 'Trang ' + kq.data.page + '/' + kq.data.total_pages + ' · ' + kq.data.total + ' đề tài' : '';

            if (!kq.data.items.length) {
                ds.append(tao('p', { class: 'phu', text: 'Không tìm thấy đề tài phù hợp.' }));
            } else {
                const bang = tao('table');
                const dau = tao('thead', {}, tao('tr', {},
                    tao('th', { scope: 'col', text: 'Tên đề tài' }),
                    tao('th', { scope: 'col', text: 'Trạng thái' }),
                    tao('th', { scope: 'col', text: 'Lớp học phần' }),
                    tao('th', { scope: 'col', text: 'Ngày tạo' })));
                const than = tao('tbody');
                kq.data.items.forEach((dt) => {
                    than.append(tao('tr', {},
                        tao('td', { text: dt.ten_de_tai || '' }),
                        tao('td', {}, nhanTrangThai(dt.trang_thai)),
                        tao('td', { text: String(dt.lop_hoc_phan_id ?? '') }),
                        tao('td', { text: thoiGian(dt.created_at) })));
                });
                bang.append(dau, than);
                ds.append(tao('div', { class: 'cuon-ngang' }, bang));
            }

            const tongTrang = Number(kq.data.total_pages || 0);
            phanTrang.append(
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang trước', disabled: trang <= 1, onclick: () => { trang -= 1; taiDs(); } }),
                tao('span', { class: 'phu', text: tongTrang ? ('Trang ' + trang + '/' + tongTrang) : 'Không có trang' }),
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang sau', disabled: !tongTrang || trang >= tongTrang, onclick: () => { trang += 1; taiDs(); } })
            );
        }

        nutTim.addEventListener('click', () => { trang = 1; taiDs(); });
        nutXoa.addEventListener('click', () => {
            tuKhoa.value = '';
            trangThai.value = '';
            lopHocPhan.value = '';
            sapXep.value = 'created_at';
            huong.value = 'DESC';
            trang = 1;
            taiDs();
        });
        await taiDs();
    }

    // ---------- Sinh vien ----------
    async function khoiSinhVien() {
        const the = theMoi('Đề tài của tôi');
        const ds = tao('div');
        const oLoi = oThongBao();
        const kqLop = await goi('GET', '/lop-hoc-phan');
        const lop = kqLop.ok ? kqLop.data : [];
        const form = tao('form', { class: 'luoi-form' },
            oNhap('Tên đề tài', 'ten_de_tai', { maxlength: 200, required: true }),
            tao('label', {}, 'Lớp học phần', tao('select', { name: 'lop_hoc_phan_id' }, lop.map((l) => tao('option', { value: l.id, text: l.ma_lop + ' – ' + l.ten_lop })))),
            tao('label', {}, 'Mô tả phạm vi', tao('textarea', { name: 'mo_ta_pham_vi', required: true })),
            tao('button', { type: 'submit', text: 'Đề xuất đề tài' }));
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const f = new FormData(form);
            thucHien(form.querySelector('button'), oLoi, () => goi('POST', '/de-tai', {
                ten_de_tai: f.get('ten_de_tai'), mo_ta_pham_vi: f.get('mo_ta_pham_vi'), lop_hoc_phan_id: Number(f.get('lop_hoc_phan_id')),
            }), async () => { form.reset(); await taiDs(); await taiThongBao(); });
        });
        the.append(ds, tao('h3', { text: 'Đề xuất đề tài mới' }), form, oLoi);

        async function taiDs() {
            const kq = await goi('GET', '/de-tai?size=50');
            ds.replaceChildren();
            if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            if (!kq.data.items.length) ds.append(tao('p', { class: 'phu', text: 'Bạn chưa có đề tài nào.' }));
            kq.data.items.forEach((dt) => ds.append(theDeTaiSinhVien(dt, taiDs)));
        }
        await taiDs();
    }

    function theDeTaiSinhVien(dt, taiDs) {
        const laNhomTruong = Number(dt.sinh_vien_de_xuat_id) === toiId;
        const oLoi = oThongBao();
        const vungNhom = tao('div');
        const vungBaoCao = tao('div');
        const vungDiem = tao('div');
        const hang = tao('div', { class: 'hang' },
            tao('button', { type: 'button', class: 'nut-phu', text: 'Xem nhóm', onclick: () => hienNhom(vungNhom, dt, laNhomTruong) }));
        if (CO_BAO_CAO.includes(dt.trang_thai)) {
            hang.append(tao('button', { type: 'button', class: 'nut-phu', text: 'Báo cáo theo mốc', onclick: () => hienBaoCao(vungBaoCao, dt, laNhomTruong, false) }));
        }
        if (NGHIEM_THU.includes(dt.trang_thai)) {
            hang.append(tao('button', { type: 'button', class: 'nut-phu', text: 'Điểm nghiệm thu', onclick: () => hienDiem(vungDiem, dt) }));
        }
        if (laNhomTruong && ['pending', 'rejected'].includes(dt.trang_thai)) {
            hang.append(tao('button', {
                type: 'button', class: 'nut-nguy', text: 'Xóa đề xuất',
                onclick: (e) => { if (window.confirm('Xóa đề xuất "' + dt.ten_de_tai + '"?')) thucHien(e.target, oLoi, () => goi('DELETE', '/de-tai/' + dt.id), taiDs); },
            }));
        }
        let suaForm = null;
        if (laNhomTruong && ['pending', 'rejected'].includes(dt.trang_thai)) {
            const ten = tao('input', { name: 'ten_de_tai', value: dt.ten_de_tai, maxlength: 200 });
            const moTa = tao('textarea', { name: 'mo_ta_pham_vi' });
            moTa.value = dt.mo_ta_pham_vi || '';
            suaForm = tao('div', { class: 'luoi-form' }, tao('label', {}, 'Sửa tên', ten), tao('label', {}, 'Sửa mô tả', moTa),
                tao('button', { type: 'button', text: dt.trang_thai === 'rejected' ? 'Sửa và gửi lại' : 'Lưu sửa', onclick: (e) => thucHien(e.target, oLoi, () => goi('PATCH', '/de-tai/' + dt.id, { ten_de_tai: ten.value, mo_ta_pham_vi: moTa.value }), taiDs) }));
        }
        return tao('div', { class: 'muc' },
            tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
            tao('p', { class: 'phu', text: dt.mo_ta_pham_vi || '' }),
            thanhTienDo(dt.trang_thai),
            dt.trang_thai === 'pending' && !dt.gvhd_id ? tao('p', { class: 'phu', text: 'Đang chờ thư ký khoa phân công GVHD.' }) : null,
            suaForm, hang, oLoi, vungNhom, vungBaoCao, vungDiem);
    }

    async function hienNhom(vung, dt, laNhomTruong) {
        const kq = await goi('GET', '/nhom-sinh-vien/' + dt.id);
        vung.replaceChildren();
        if (!kq.ok) { vung.append(tao('p', { class: 'loi', text: kq.loi })); return; }
        const oLoi = oThongBao();
        const taiLai = () => hienNhom(vung, dt, laNhomTruong);
        vung.append(tao('div', { class: 'muc-con' },
            tao('p', { text: 'GVHD: ' + (kq.data.gvhd ? kq.data.gvhd.ho_ten + ' (' + kq.data.gvhd.email + ')' : 'chưa phân công') }),
            tao('ul', {}, kq.data.thanh_vien.map((tv) => tao('li', {},
                tv.ho_ten + ' – ' + (tv.vai_tro_nhom === 'leader' ? 'nhóm trưởng' : 'thành viên') + ' (mã ' + tv.sinh_vien_id + ') ',
                laNhomTruong && tv.vai_tro_nhom !== 'leader'
                    ? tao('button', { type: 'button', class: 'nut-nguy', text: 'Xóa', onclick: (e) => thucHien(e.target, oLoi, () => goi('DELETE', '/nhom-sinh-vien/' + dt.id + '/thanh-vien/' + tv.sinh_vien_id), taiLai) })
                    : null))),
            laNhomTruong ? (() => {
                const ma = tao('input', { type: 'number', min: 1 });
                return tao('div', { class: 'hang' }, tao('label', {}, 'Mã người dùng của sinh viên', ma), tao('button', { type: 'button', text: 'Thêm thành viên', onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/nhom-sinh-vien/' + dt.id + '/thanh-vien', { sinh_vien_id: Number(ma.value) }), taiLai) }));
            })() : null,
            oLoi));
    }

    // ---------- GVHD ----------
    async function khoiGvhd() {
        const the = theMoi('Đề tài tôi phụ trách');
        const ds = tao('div');
        the.append(ds);
        async function taiDs() {
            const kq = await goi('GET', '/de-tai?size=100');
            ds.replaceChildren();
            if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            if (!kq.data.items.length) ds.append(tao('p', { class: 'phu', text: 'Chưa có đề tài nào được phân công.' }));
            kq.data.items.forEach((dt) => {
                const oLoi = oThongBao();
                const vungBaoCao = tao('div');
                const vungDiem = tao('div');
                const hang = tao('div', { class: 'hang' });
                if (dt.trang_thai === 'pending') {
                    const ghiChu = tao('input', { placeholder: 'Nhận xét / lý do từ chối', 'aria-label': 'Nhận xét / lý do từ chối – đề tài ' + dt.id });
                    hang.append(tao('label', {}, 'Nhận xét / lý do từ chối', ghiChu),
                        tao('button', { type: 'button', text: 'Duyệt', onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/de-tai/' + dt.id + '/duyet', { nhan_xet: ghiChu.value }), taiDs) }),
                        tao('button', { type: 'button', class: 'nut-nguy', text: 'Từ chối', onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/de-tai/' + dt.id + '/tu-choi', { ly_do: ghiChu.value }), taiDs) }));
                }
                if (CO_BAO_CAO.includes(dt.trang_thai)) {
                    hang.append(tao('button', { type: 'button', class: 'nut-phu', text: 'Báo cáo theo mốc', onclick: () => hienBaoCao(vungBaoCao, dt, false, true) }));
                }
                if (NGHIEM_THU.includes(dt.trang_thai)) {
                    hang.append(tao('button', { type: 'button', class: 'nut-phu', text: 'Điểm nghiệm thu', onclick: () => hienDiem(vungDiem, dt) }));
                }
                ds.append(tao('div', { class: 'muc' },
                    tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
                    tao('p', { class: 'phu', text: dt.mo_ta_pham_vi || '' }), thanhTienDo(dt.trang_thai), hang, oLoi, vungBaoCao, vungDiem));
            });
        }
        await taiDs();
    }

    // ---------- Bao cao thong ke (K7): thu ky xem toan khoa va xuat CSV, GVHD xem de tai minh huong dan ----------
    // Mau theo trang thai giu co dinh o moi bieu do (mau di theo doi tuong, khong theo thu hang)
    const MAU = {
        pending: '#2a78d6', approved: '#eb6834', rejected: '#1baf7a', in_progress: '#eda100',
        completed: '#e87ba4', accepted: '#008300', chua: '#cbd5e1',
    };
    const BAO_CAO = [
        {
            loai: 'de-tai-theo-lop', moTa: 'Số đề tài đề xuất trong khoảng thời gian, theo lớp học phần và trạng thái.',
            nhan: (d) => d.ma_lop, chiTiet: (d) => d.ma_lop + ' – ' + d.ten_lop, loc: (d) => d.tong_de_tai > 0,
            phan: [
                { khoa: 'cho_duyet', nhan: 'Chờ duyệt', mau: MAU.pending },
                { khoa: 'da_duyet', nhan: 'Đã duyệt', mau: MAU.approved },
                { khoa: 'bi_tu_choi', nhan: 'Bị từ chối', mau: MAU.rejected },
                { khoa: 'dang_thuc_hien', nhan: 'Đang thực hiện', mau: MAU.in_progress },
                { khoa: 'du_dieu_kien_nghiem_thu', nhan: 'Đủ điều kiện nghiệm thu', mau: MAU.completed },
                { khoa: 'da_nghiem_thu', nhan: 'Đã nghiệm thu', mau: MAU.accepted },
            ],
        },
        {
            loai: 'tien-do-nop', moTa: 'Mốc có hạn nộp trong khoảng thời gian: đề tài nộp đúng hạn, nộp trễ (theo bản nộp đầu tiên) và chưa nộp.',
            nhan: (d) => d.ma_lop + ' · ' + d.ten_moc, chiTiet: (d) => d.ma_lop + ' · ' + d.ten_moc + ' (hạn ' + thoiGian(d.han_nop) + ')',
            loc: (d) => d.so_de_tai_can_nop > 0,
            phan: [
                { khoa: 'nop_dung_han', nhan: 'Nộp đúng hạn', mau: MAU.pending },
                { khoa: 'nop_tre', nhan: 'Nộp trễ', mau: MAU.approved },
                { khoa: 'chua_nop', nhan: 'Chưa nộp', mau: MAU.chua },
            ],
        },
        {
            loai: 'gvhd', moTa: 'Đề tài đề xuất trong khoảng thời gian, theo GVHD và giai đoạn; điểm chỉ tính đề tài đã chốt nghiệm thu.',
            nhan: (d) => d.giang_vien, chiTiet: (d) => d.giang_vien + (d.diem_trung_binh !== null ? ' – điểm TB ' + d.diem_trung_binh : ''),
            loc: (d) => d.so_de_tai_huong_dan > 0,
            phan: [
                { khoa: 'cho_duyet', nhan: 'Chờ duyệt', mau: MAU.pending },
                { khoa: 'bi_tu_choi', nhan: 'Bị từ chối', mau: MAU.rejected },
                { khoa: 'dang_thuc_hien', nhan: 'Đang thực hiện (gồm đã duyệt)', mau: MAU.in_progress },
                { khoa: 'du_dieu_kien_nghiem_thu', nhan: 'Đủ điều kiện nghiệm thu', mau: MAU.completed },
                { khoa: 'da_nghiem_thu', nhan: 'Đã nghiệm thu', mau: MAU.accepted },
            ],
        },
    ];

    function svg(the, thuocTinh, ...con) {
        const e = document.createElementNS('http://www.w3.org/2000/svg', the);
        Object.entries(thuocTinh || {}).forEach(([k, v]) => {
            if (k === 'text') e.textContent = v; else e.setAttribute(k, v);
        });
        con.forEach((c) => c && e.append(c));
        return e;
    }

    /** Cot chong nam ngang: moi dong mot doi tuong, moi doan mot thanh phan; nhan tong o cuoi cot */
    function veCotChong(cfg, dong, tieuDe) {
        const RONG_NHAN = 210, RONG_COT = 520, CAO = 22, KHOANG = 10, TREN = 22;
        const tong = (d) => cfg.phan.reduce((s, p) => s + (Number(d[p.khoa]) || 0), 0);
        const lon = Math.max(1, ...dong.map(tong));
        const buoc = Math.max(1, Math.ceil(lon / 4));
        const cao = TREN + dong.length * (CAO + KHOANG);
        const ve = svg('svg', { viewBox: '0 0 ' + (RONG_NHAN + RONG_COT + 50) + ' ' + cao, role: 'img', 'aria-label': tieuDe + '. Số liệu chi tiết ở bảng bên dưới.', class: 'bieu-do' });
        for (let x = 0; x <= lon; x += buoc) {
            const vx = RONG_NHAN + (x / lon) * RONG_COT;
            ve.append(svg('line', { x1: vx, x2: vx, y1: TREN - 6, y2: cao, class: 'luoi' }),
                svg('text', { x: vx, y: TREN - 10, 'text-anchor': 'middle', class: 'truc', text: String(x) }));
        }
        dong.forEach((d, i) => {
            const y = TREN + i * (CAO + KHOANG);
            const ten = cfg.nhan(d);
            ve.append(svg('text', { x: RONG_NHAN - 8, y: y + CAO / 2 + 4, 'text-anchor': 'end', class: 'nhan-dong' },
                svg('title', { text: cfg.chiTiet(d) }), document.createTextNode(ten.length > 30 ? ten.slice(0, 29) + '…' : ten)));
            let x = RONG_NHAN;
            cfg.phan.forEach((p) => {
                const v = Number(d[p.khoa]) || 0;
                if (!v) return;
                const w = (v / lon) * RONG_COT;
                ve.append(svg('rect', { x: x, y: y, width: Math.max(1, w - 2), height: CAO, rx: 3, fill: p.mau, class: 'doan' },
                    svg('title', { text: cfg.chiTiet(d) + '\n' + p.nhan + ': ' + v })));
                x += w;
            });
            ve.append(svg('text', { x: x + 6, y: y + CAO / 2 + 4, class: 'nhan-tong', text: String(tong(d)) }));
        });
        return ve;
    }

    function chuGiai(phan) {
        return tao('div', { class: 'chu-giai' }, phan.map((p) => tao('span', {}, tao('span', { class: 'o-mau', style: 'background:' + p.mau }), p.nhan)));
    }

    function bangSoLieu(baoCao) {
        return tao('div', { class: 'cuon-ngang' }, tao('table', {},
            tao('thead', {}, tao('tr', {}, baoCao.cot.map((c) => tao('th', { class: c.la_so ? 'so' : '', text: c.nhan })))),
            tao('tbody', {}, baoCao.dong.map((d) => tao('tr', {}, baoCao.cot.map((c) => tao('td', {
                class: c.la_so ? 'so' : '', text: d[c.khoa] === null ? '—' : (c.khoa === 'han_nop' ? thoiGian(d[c.khoa]) : String(d[c.khoa])),
            })))))));
    }

    async function khoiBaoCao(choXuat) {
        const the = theMoi('Báo cáo thống kê');
        const nam = new Date().getFullYear();
        const tu = tao('input', { type: 'date', value: nam + '-01-01', 'aria-label': 'Từ ngày' });
        const den = tao('input', { type: 'date', value: nam + '-12-31', 'aria-label': 'Đến ngày' });
        const oLoi = oThongBao();
        const vung = BAO_CAO.map(() => tao('div'));
        const nutXem = tao('button', { type: 'button', text: 'Xem báo cáo', onclick: () => taiTatCa() });
        the.append(tao('div', { class: 'luoi-form' }, tao('label', {}, 'Từ ngày', tu), tao('label', {}, 'Đến ngày', den), nutXem), oLoi, ...vung);

        async function taiTatCa() {
            oLoi.textContent = '';
            nutXem.disabled = true;
            const q = '?tu_ngay=' + encodeURIComponent(tu.value) + '&den_ngay=' + encodeURIComponent(den.value);
            const kq = await Promise.all(BAO_CAO.map((b) => goi('GET', '/thong-ke/' + b.loai + q)));
            nutXem.disabled = false;
            kq.forEach((k, i) => {
                const cfg = BAO_CAO[i];
                vung[i].replaceChildren();
                if (!k.ok) { vung[i].append(tao('p', { class: 'loi', text: k.loi })); return; }
                const bc = k.data;
                const coSo = bc.dong.filter(cfg.loc);
                vung[i].append(tao('div', { class: 'khoi-bao-cao' },
                    tao('div', { class: 'hang' }, tao('h3', { text: (i + 1) + '. ' + bc.tieu_de }),
                        choXuat ? tao('a', { class: 'nut-tai', href: API + '/thong-ke/' + cfg.loai + '/xuat' + q + '&dinh_dang=csv', download: '', text: 'Xuất CSV' }) : null),
                    tao('p', { class: 'phu', text: cfg.moTa + ' Từ ' + bc.tu_ngay + ' đến ' + bc.den_ngay + (bc.pham_vi === 'gvhd' ? ' – chỉ đề tài bạn hướng dẫn.' : ' – toàn khoa.') }),
                    coSo.length ? [chuGiai(cfg.phan), veCotChong(cfg, coSo, bc.tieu_de)] : tao('p', { class: 'phu', text: 'Không có dữ liệu trong khoảng thời gian đã chọn.' }),
                    tao('details', {}, tao('summary', { text: 'Bảng số liệu (' + bc.dong.length + ' dòng)' }), bangSoLieu(bc))));
            });
        }
        await taiTatCa();
    }

    // ---------- K8: Giao dien tra cuu nhat ky he thong (V3) ----------
    async function khoiNhatKyHeThong() {
        const the = theMoi('Tra cứu nhật ký hệ thống');
        let trang = 1;
        const nguoiDungId = tao('input', { type: 'number', min: 1, placeholder: 'Mã người dùng', 'aria-label': 'Mã người dùng' });
        const hanhDong = tao('input', { placeholder: 'Ví dụ: PROPOSE_TOPIC', 'aria-label': 'Hành động' });
        const tuKhoa = tao('input', { placeholder: 'Từ khóa', 'aria-label': 'Từ khóa nhật ký' });
        const tuNgay = tao('input', { type: 'date', 'aria-label': 'Từ ngày' });
        const denNgay = tao('input', { type: 'date', 'aria-label': 'Đến ngày' });
        const nutTim = tao('button', { type: 'button', text: 'Tra cứu' });
        const nutXoa = tao('button', { type: 'button', class: 'nut-phu', text: 'Xóa bộ lọc' });
        const ds = tao('div');
        const phanTrang = tao('div', { class: 'hang' });
        const oThongTin = tao('p', { class: 'phu', 'aria-live': 'polite' });
        const oLoi = oThongBao();

        the.append(
            tao('div', { class: 'luoi-form' },
                tao('label', {}, 'Người dùng', nguoiDungId),
                tao('label', {}, 'Hành động', hanhDong),
                tao('label', {}, 'Từ khóa', tuKhoa),
                tao('label', {}, 'Từ ngày', tuNgay),
                tao('label', {}, 'Đến ngày', denNgay)),
            tao('div', { class: 'hang' }, nutTim, nutXoa),
            oLoi, oThongTin, ds, phanTrang);

        async function taiDs() {
            const thamSo = new URLSearchParams({ page: String(trang), size: '10' });
            if (nguoiDungId.value) thamSo.set('nguoi_dung_id', nguoiDungId.value);
            if (hanhDong.value.trim()) thamSo.set('hanh_dong', hanhDong.value.trim());
            if (tuKhoa.value.trim()) thamSo.set('tu_khoa', tuKhoa.value.trim());
            if (tuNgay.value) thamSo.set('tu_ngay', tuNgay.value);
            if (denNgay.value) thamSo.set('den_ngay', denNgay.value);

            ds.replaceChildren(tao('p', { class: 'phu', text: 'Đang tải...' }));
            phanTrang.replaceChildren();
            const kq = await goi('GET', '/nhat-ky-he-thong?' + thamSo.toString());
            ds.replaceChildren();
            if (!kq.ok) {
                oLoi.textContent = kq.loi;
                oThongTin.textContent = '';
                return;
            }
            oLoi.textContent = '';
            oThongTin.textContent = Number(kq.data.total) ? 'Trang ' + kq.data.page + '/' + kq.data.total_pages + ' · ' + kq.data.total + ' bản ghi' : '';

            if (!kq.data.items.length) {
                ds.append(tao('p', { class: 'phu', text: 'Không có nhật ký phù hợp.' }));
            } else {
                const bang = tao('table');
                bang.append(tao('thead', {}, tao('tr', {},
                    tao('th', { scope: 'col', text: 'Thời gian' }),
                    tao('th', { scope: 'col', text: 'Người dùng' }),
                    tao('th', { scope: 'col', text: 'Hành động' }),
                    tao('th', { scope: 'col', text: 'Chi tiết' }),
                    tao('th', { scope: 'col', text: 'IP' }))));
                const than = tao('tbody');
                kq.data.items.forEach((nk) => {
                    than.append(tao('tr', {},
                        tao('td', { text: thoiGian(nk.created_at) }),
                        tao('td', { text: nk.ho_ten ? nk.ho_ten + ' (' + (nk.ten_dang_nhap || '') + ')' : '—' }),
                        tao('td', { text: nk.hanh_dong || '' }),
                        tao('td', { text: nk.chi_tiet || '' }),
                        tao('td', { text: nk.dia_chi_ip || '—' })));
                });
                bang.append(than);
                ds.append(tao('div', { class: 'cuon-ngang' }, bang));
            }

            const tongTrang = Number(kq.data.total_pages || 0);
            phanTrang.append(
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang trước', disabled: trang <= 1, onclick: () => { trang -= 1; taiDs(); } }),
                tao('span', { class: 'phu', text: tongTrang ? ('Trang ' + trang + '/' + tongTrang) : 'Không có trang' }),
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang sau', disabled: !tongTrang || trang >= tongTrang, onclick: () => { trang += 1; taiDs(); } })
            );
        }

        nutTim.addEventListener('click', () => { trang = 1; taiDs(); });
        nutXoa.addEventListener('click', () => {
            nguoiDungId.value = '';
            hanhDong.value = '';
            tuKhoa.value = '';
            tuNgay.value = '';
            denNgay.value = '';
            trang = 1;
            taiDs();
        });
        await taiDs();
    }

    // ---------- Thu ky khoa ----------
    async function khoiPhanCong() {
        const the = theMoi('Đề tài chờ duyệt – phân công GVHD');
        const ds = tao('div');
        the.append(ds);
        const kqGv = await goi('GET', '/nguoi-dung?vai_tro=GVHD&size=100');
        const gvhd = kqGv.ok ? kqGv.data.items.filter((g) => !g.bi_khoa) : [];
        const tenGv = Object.fromEntries(gvhd.map((g) => [String(g.id), g.ho_ten]));
        async function taiDs() {
            const kq = await goi('GET', '/de-tai?trang_thai=pending&size=100');
            ds.replaceChildren();
            if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            if (!kq.data.items.length) ds.append(tao('p', { class: 'phu', text: 'Không có đề tài nào đang chờ duyệt.' }));
            kq.data.items.forEach((dt) => {
                const oLoi = oThongBao();
                const chon = tao('select', { 'aria-label': 'Chọn GVHD cho đề tài ' + dt.id },
                    gvhd.map((g) => tao('option', { value: g.id, text: g.ho_ten, selected: String(g.id) === String(dt.gvhd_id) })));
                ds.append(tao('div', { class: 'muc' },
                    tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
                    tao('p', { class: 'phu', text: 'GVHD hiện tại: ' + (dt.gvhd_id ? (tenGv[String(dt.gvhd_id)] || 'mã ' + dt.gvhd_id) : 'chưa phân công') }),
                    tao('div', { class: 'hang' }, tao('label', {}, 'Chọn GVHD', chon), tao('button', { type: 'button', text: 'Phân công', onclick: (e) => thucHien(e.target, oLoi, () => goi('PATCH', '/nhom-sinh-vien/' + dt.id + '/gvhd', { gvhd_id: Number(chon.value) }), taiDs) })),
                    oLoi));
            });
        }
        await taiDs();
    }

    async function khoiChotNghiemThu() {
        const the = theMoi('Chốt kết quả nghiệm thu');
        const ds = tao('div');
        the.append(ds);
        async function taiDs() {
            const kq = await goi('GET', '/de-tai?trang_thai=completed&size=100');
            ds.replaceChildren();
            if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            if (!kq.data.items.length) ds.append(tao('p', { class: 'phu', text: 'Không có đề tài nào chờ chốt nghiệm thu.' }));
            kq.data.items.forEach((dt) => {
                const oLoi = oThongBao();
                const vungDiem = tao('div');
                ds.append(tao('div', { class: 'muc' },
                    tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
                    vungDiem,
                    tao('div', { class: 'hang' }, tao('button', {
                        type: 'button', text: 'Chốt nghiệm thu',
                        onclick: (e) => { if (window.confirm('Chốt kết quả nghiệm thu "' + dt.ten_de_tai + '"? Sau khi chốt hội đồng không chấm thêm được.')) thucHien(e.target, oLoi, () => goi('POST', '/de-tai/' + dt.id + '/chot-nghiem-thu'), taiDs); },
                    })), oLoi));
                hienDiem(vungDiem, dt);
            });
        }
        await taiDs();
    }

    async function khoiLopVaMoc() {
        const the = theMoi('Lớp học phần và mốc thời gian');
        const oLoi = oThongBao();
        const chonLop = tao('select');
        const vungMoc = tao('div');
        const formLop = tao('form', { class: 'luoi-form' },
            oNhap('Mã lớp', 'ma_lop', { maxlength: 20, required: true }), oNhap('Tên lớp', 'ten_lop', { maxlength: 100, required: true }),
            tao('label', {}, 'Học kỳ', tao('select', { name: 'hoc_ky' }, ['HK1', 'HK2', 'HK3'].map((h) => tao('option', { value: h, text: h })))),
            oNhap('Năm học', 'nam_hoc', { maxlength: 20, placeholder: '2026-2027', required: true }),
            tao('button', { type: 'submit', text: 'Thêm lớp' }));
        formLop.addEventListener('submit', (e) => {
            e.preventDefault();
            const f = Object.fromEntries(new FormData(formLop));
            thucHien(formLop.querySelector('button'), oLoi, () => goi('POST', '/lop-hoc-phan', f), async () => { formLop.reset(); await taiLop(); });
        });
        const nutXoaLop = tao('button', {
            type: 'button', class: 'nut-nguy', text: 'Xóa lớp đang chọn',
            onclick: (e) => { if (chonLop.value && window.confirm('Xóa lớp này?')) thucHien(e.target, oLoi, () => goi('DELETE', '/lop-hoc-phan/' + chonLop.value), taiLop); },
        });
        chonLop.addEventListener('change', () => taiMoc());
        the.append(tao('div', { class: 'hang' }, tao('label', {}, 'Lớp học phần', chonLop), nutXoaLop), vungMoc, tao('h3', { text: 'Thêm lớp học phần' }), formLop, oLoi);

        async function taiLop() {
            const kq = await goi('GET', '/lop-hoc-phan');
            const dangChon = chonLop.value;
            chonLop.replaceChildren(...(kq.ok ? kq.data : []).map((l) => tao('option', { value: l.id, text: l.ma_lop + ' – ' + l.ten_lop + ' (' + l.hoc_ky + ' ' + l.nam_hoc + ')' })));
            if (dangChon && [...chonLop.options].some((o) => o.value === dangChon)) chonLop.value = dangChon;
            await taiMoc();
        }

        async function taiMoc() {
            vungMoc.replaceChildren();
            if (!chonLop.value) return;
            const oLoiMoc = oThongBao();
            const kq = await goi('GET', '/moc-thoi-gian?lop_hoc_phan_id=' + chonLop.value);
            const bang = tao('table', {}, tao('thead', {}, tao('tr', {}, ['Mốc', 'Hạn nộp', 'Bắt buộc', ''].map((t) => tao('th', { text: t })))));
            const than = tao('tbody');
            (kq.ok ? kq.data : []).forEach((m) => {
                const han = tao('input', { type: 'datetime-local', value: String(m.han_nop).slice(0, 16).replace(' ', 'T'), 'aria-label': 'Hạn nộp ' + m.ten_moc });
                than.append(tao('tr', {}, tao('td', { text: m.ten_moc }), tao('td', {}, han), tao('td', { text: Number(m.bat_buoc) ? 'Có' : 'Không' }),
                    tao('td', {}, tao('div', { class: 'hang' },
                        tao('button', { type: 'button', class: 'nut-phu', text: 'Lưu hạn', onclick: (e) => thucHien(e.target, oLoiMoc, () => goi('PATCH', '/moc-thoi-gian/' + m.id, { han_nop: han.value }), taiMoc) }),
                        tao('button', { type: 'button', class: 'nut-nguy', text: 'Xóa', onclick: (e) => { if (window.confirm('Xóa mốc "' + m.ten_moc + '"?')) thucHien(e.target, oLoiMoc, () => goi('DELETE', '/moc-thoi-gian/' + m.id), taiMoc); } })))));
            });
            bang.append(than);
            const formMoc = tao('form', { class: 'luoi-form' },
                oNhap('Tên mốc', 'ten_moc', { maxlength: 100, required: true }), oNhap('Hạn nộp', 'han_nop', { type: 'datetime-local', required: true }),
                oNhap('Mô tả', 'mo_ta'), tao('button', { type: 'submit', text: 'Thêm mốc' }));
            formMoc.addEventListener('submit', (e) => {
                e.preventDefault();
                const f = Object.fromEntries(new FormData(formMoc));
                thucHien(formMoc.querySelector('button'), oLoiMoc, () => goi('POST', '/moc-thoi-gian', Object.assign(f, { lop_hoc_phan_id: Number(chonLop.value) })), taiMoc);
            });
            vungMoc.append(tao('div', { class: 'cuon-ngang' }, bang), tao('h3', { text: 'Thêm mốc cho lớp đang chọn' }), formMoc, oLoiMoc);
        }
        await taiLop();
    }

    async function khoiTaiKhoan() {
        const the = theMoi('Tài khoản người dùng');
        const oLoi = oThongBao();
        const tuKhoa = tao('input', { type: 'search', placeholder: 'Tìm tên đăng nhập, họ tên, email' });
        const locVaiTro = tao('select', {}, tao('option', { value: '', text: 'Mọi vai trò' }),
            Object.entries(TEN_VAI_TRO).map(([ma, ten]) => tao('option', { value: ma, text: ten })));
        const vungBang = tao('div', { class: 'cuon-ngang' });
        const phanTrang = tao('div', { class: 'hang' });
        const oMa = tao('div', { class: 'o-ma-dat-lai', role: 'status', 'aria-live': 'polite' });
        let trang = 1;
        const formTk = tao('form', { class: 'luoi-form' },
            oNhap('Tên đăng nhập', 'ten_dang_nhap', { maxlength: 50, required: true }), oNhap('Mật khẩu', 'mat_khau', { type: 'password', minlength: 8, maxlength: 72, required: true, autocomplete: 'new-password' }),
            oNhap('Họ tên', 'ho_ten', { maxlength: 100, required: true }), oNhap('Email', 'email', { type: 'email', maxlength: 100, required: true }),
            tao('label', {}, 'Vai trò', tao('select', { name: 'vai_tro' }, Object.entries(TEN_VAI_TRO).map(([ma, ten]) => tao('option', { value: ma, text: ten, selected: ma === 'GVHD' })))),
            tao('button', { type: 'submit', text: 'Tạo tài khoản' }));
        formTk.addEventListener('submit', (e) => {
            e.preventDefault();
            const f = Object.fromEntries(new FormData(formTk));
            thucHien(formTk.querySelector('button'), oLoi, () => goi('POST', '/nguoi-dung', f), async () => { formTk.reset(); await taiDs(); });
        });
        the.append(tao('div', { class: 'hang' }, tao('label', {}, 'Từ khóa', tuKhoa), tao('label', {}, 'Vai trò', locVaiTro), tao('button', { type: 'button', text: 'Tìm', onclick: () => { trang = 1; taiDs(); } })),
            vungBang, phanTrang, oMa, tao('h3', { text: 'Tạo tài khoản' }), formTk, oLoi);

        // F1.3: thu ky kiem dung nguoi roi cap ma dat lai mot lan; ma chi hien mot lan tren man hinh nay
        async function capMa(nut, nd) {
            if (!window.confirm('Cấp mã đặt lại mật khẩu cho ' + nd.ten_dang_nhap + '? Chỉ cấp sau khi đã kiểm đúng người.')) return;
            const kq = await thucHien(nut, oLoi, () => goi('POST', '/nguoi-dung/' + nd.id + '/ma-dat-lai'));
            if (!kq.ok) return;
            oMa.replaceChildren(
                tao('p', {}, 'Mã đặt lại cho ', tao('strong', { text: kq.data.ten_dang_nhap }), ': ', tao('strong', { class: 'ma', text: kq.data.ma })),
                tao('p', { class: 'phu', text: 'Hết hạn lúc ' + thoiGian(kq.data.het_han) + ' (' + kq.data.hieu_luc_phut + ' phút), dùng một lần. Mã chỉ hiện một lần, hãy đọc trực tiếp cho người dùng.' }));
        }

        async function taiDs() {
            const q = new URLSearchParams({ page: trang, size: 20 });
            if (tuKhoa.value.trim()) q.set('q', tuKhoa.value.trim());
            if (locVaiTro.value) q.set('vai_tro', locVaiTro.value);
            const kq = await goi('GET', '/nguoi-dung?' + q);
            vungBang.replaceChildren();
            phanTrang.replaceChildren();
            if (!kq.ok) { vungBang.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            const than = tao('tbody');
            kq.data.items.forEach((nd) => {
                const doiVaiTro = tao('select', { 'aria-label': 'Vai trò của ' + nd.ten_dang_nhap },
                    Object.entries(TEN_VAI_TRO).map(([ma, ten]) => tao('option', { value: ma, text: ten, selected: nd.vai_tro.includes(ma) })));
                than.append(tao('tr', {},
                    tao('td', { text: nd.id }), tao('td', { text: nd.ten_dang_nhap }), tao('td', { text: nd.ho_ten }), tao('td', { text: nd.email }),
                    tao('td', {}, tao('div', { class: 'hang' }, doiVaiTro,
                        tao('button', { type: 'button', class: 'nut-phu', text: 'Đổi', onclick: (e) => thucHien(e.target, oLoi, () => goi('PATCH', '/nguoi-dung/' + nd.id + '/trang-thai', { vai_tro: doiVaiTro.value }), taiDs) }))),
                    tao('td', {}, tao('button', {
                        type: 'button', class: nd.bi_khoa ? '' : 'nut-nguy', text: nd.bi_khoa ? 'Mở khóa' : 'Khóa',
                        onclick: (e) => thucHien(e.target, oLoi, () => goi('PATCH', '/nguoi-dung/' + nd.id + '/trang-thai', { khoa: !nd.bi_khoa }), taiDs),
                    })),
                    tao('td', {}, tao('button', { type: 'button', class: 'nut-phu', text: 'Cấp mã', 'aria-label': 'Cấp mã đặt lại mật khẩu cho ' + nd.ten_dang_nhap, onclick: (e) => capMa(e.target, nd) }))));
            });
            vungBang.append(tao('table', {}, tao('thead', {}, tao('tr', {}, ['Mã', 'Tên đăng nhập', 'Họ tên', 'Email', 'Vai trò', 'Trạng thái', 'Đặt lại mật khẩu'].map((t) => tao('th', { text: t })))), than));
            const soTrang = Math.max(1, Math.ceil(kq.data.total / kq.data.size));
            phanTrang.append(
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang trước', disabled: trang <= 1, onclick: () => { trang -= 1; taiDs(); } }),
                tao('span', { class: 'phu', text: 'Trang ' + trang + '/' + soTrang + ' · ' + kq.data.total + ' tài khoản' }),
                tao('button', { type: 'button', class: 'nut-phu', text: 'Trang sau', disabled: trang >= soTrang, onclick: () => { trang += 1; taiDs(); } }));
        }
        await taiDs();
    }

    // ---------- Hoi dong ----------
    async function khoiHoiDong() {
        const the = theMoi('Hồ sơ nghiệm thu');
        const locTt = tao('select', { onchange: () => taiDs() },
            tao('option', { value: 'completed', text: 'Chờ chấm' }), tao('option', { value: 'accepted', text: 'Đã nghiệm thu' }), tao('option', { value: '', text: 'Tất cả' }));
        const ds = tao('div');
        the.append(tao('div', { class: 'hang' }, tao('label', {}, 'Trạng thái hồ sơ', locTt)), ds);
        async function taiDs() {
            const kq = await goi('GET', '/nghiem-thu/ho-so?size=100' + (locTt.value ? '&trang_thai=' + locTt.value : ''));
            ds.replaceChildren();
            if (!kq.ok) { ds.append(tao('p', { class: 'loi', text: kq.loi })); return; }
            if (!kq.data.items.length) ds.append(tao('p', { class: 'phu', text: 'Không có hồ sơ nào.' }));
            kq.data.items.forEach((dt) => {
                const vungHoSo = tao('div');
                ds.append(tao('div', { class: 'muc' },
                    tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
                    tao('p', { class: 'phu', text: (dt.so_luot_cham ? 'Điểm TB ' + dt.diem_trung_binh + ' · ' + dt.so_luot_cham + ' lượt chấm' : 'Chưa có lượt chấm') + (dt.toi_da_cham ? ' · bạn đã chấm' : '') }),
                    tao('div', { class: 'hang' }, tao('button', { type: 'button', class: 'nut-phu', text: 'Mở hồ sơ', onclick: () => hienHoSo(vungHoSo, dt.id, taiDs) })),
                    vungHoSo));
            });
        }
        await taiDs();
    }

    async function hienHoSo(vung, deTaiId, taiDs) {
        const kq = await goi('GET', '/de-tai/' + deTaiId + '/ho-so-nghiem-thu');
        vung.replaceChildren();
        if (!kq.ok) { vung.append(tao('p', { class: 'loi', text: kq.loi })); return; }
        const h = kq.data;
        const oLoi = oThongBao();
        let formCham = null;
        if (h.de_tai.trang_thai === 'completed' && !h.toi_da_cham) {
            const diem = tao('input', { name: 'diem', type: 'number', min: 0, max: 10, step: 0.25, required: true });
            const nhanXet = tao('textarea', { name: 'nhan_xet' });
            formCham = tao('div', { class: 'luoi-form' }, tao('label', {}, 'Điểm (0–10)', diem), tao('label', {}, 'Nhận xét', nhanXet),
                tao('button', {
                    type: 'button', text: 'Gửi điểm',
                    onclick: (e) => thucHien(e.target, oLoi, () => goi('POST', '/de-tai/' + deTaiId + '/diem-nghiem-thu', { diem: diem.value, nhan_xet: nhanXet.value }),
                        () => hienHoSo(vung, deTaiId, taiDs)),
                }));
        }
        vung.append(tao('div', { class: 'muc-con' },
            tao('p', { class: 'phu', text: h.de_tai.mo_ta_pham_vi || '' }),
            tao('p', { text: 'GVHD: ' + (h.gvhd ? h.gvhd.ho_ten + ' (' + h.gvhd.email + ')' : 'chưa phân công') }),
            tao('p', { text: 'Nhóm: ' + h.thanh_vien.map((tv) => tv.ho_ten + (tv.vai_tro_nhom === 'leader' ? ' (nhóm trưởng)' : '')).join(', ') }),
            tao('h3', { text: 'Báo cáo theo mốc' }),
            tao('ul', {}, h.bao_cao_moc.map((m) => tao('li', {},
                tao('strong', { text: m.ten_moc + (Number(m.bat_buoc) ? '' : ' (tự chọn)') + ': ' }),
                m.ban_nop_moi_nhat
                    ? [m.ban_nop_moi_nhat.ten_tep_goc + ' · ', nhanTrangThai(m.ban_nop_moi_nhat.trang_thai),
                        m.ban_nop_moi_nhat.nhan_xet_gv ? ' · Nhận xét GVHD: ' + m.ban_nop_moi_nhat.nhan_xet_gv : '',
                        ' ', nutTaiTep(m.ban_nop_moi_nhat.id, m.ban_nop_moi_nhat.ten_tep_goc)]
                    : 'chưa nộp'))),
            tao('h3', { text: 'Điểm đã chấm' + (h.so_luot_cham ? ' – TB ' + h.diem_trung_binh : '') }),
            h.diem.length ? tao('ul', {}, h.diem.map((c) => tao('li', { text: c.nguoi_cham + ': ' + c.diem + (c.nhan_xet ? ' – ' + c.nhan_xet : '') }))) : tao('p', { class: 'phu', text: 'Chưa có lượt chấm.' }),
            h.toi_da_cham ? tao('p', { class: 'phu', text: 'Bạn đã chấm đề tài này.' }) : null,
            formCham, oLoi));
    }

    // ---------- Ho so ca nhan, dang xuat ----------
    function ganHoSo() {
        const oKetQua = document.querySelector('#tieu-de-ho-so ~ .ket-qua');
        oKetQua.id = 'ket-qua-ho-so';
        const ganForm = (id, url, layDuLieu, thanhCong) => {
            const form = document.getElementById(id);
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const nut = form.querySelector('button');
                nut.disabled = true;
                danhDauLoi(nut, oKetQua);
                const kq = await goi('PATCH', url, layDuLieu(new FormData(form)));
                nut.disabled = false;
                if (!kq.ok) danhDauLoi(nut, oKetQua, kq.truong);
                oKetQua.className = kq.ok ? 'ket-qua' : 'loi';
                oKetQua.textContent = kq.ok ? thanhCong : kq.loi;
                if (kq.ok && id === 'form-mat-khau') form.reset();
            });
        };
        ganForm('form-ho-so', '/nguoi-dung/toi', (f) => Object.fromEntries(f), 'Đã lưu hồ sơ.');
        ganForm('form-mat-khau', '/nguoi-dung/toi/mat-khau', (f) => Object.fromEntries(f), 'Đã đổi mật khẩu.');
        document.getElementById('nut-dang-xuat').addEventListener('click', async () => {
            await fetch(API + '/auth/dang-xuat', { method: 'POST', credentials: 'same-origin' });
            window.location.href = '/dang-nhap';
        });
    }

    // ---------- Trang tong quan theo vai tro ----------
    const THU_TU_VAI_TRO = ['thu_ky_khoa', 'gvhd', 'hoi_dong', 'sinh_vien'];
    const vaiTroChinh = THU_TU_VAI_TRO.find((v) => vaiTro.includes(v)) || '';
    const MO_TA_VAI_TRO = {
        sinh_vien: 'Theo dõi tiến độ đề tài, nộp báo cáo đúng hạn và xem kết quả nghiệm thu.',
        gvhd: 'Duyệt đề tài và báo cáo của các nhóm bạn hướng dẫn.',
        thu_ky_khoa: 'Điều phối đề tài, lớp học phần, mốc thời gian và nghiệm thu toàn khoa.',
        hoi_dong: 'Xem hồ sơ đủ điều kiện và chấm điểm nghiệm thu.',
    };
    const MAU_TRANG_THAI = {
        pending: '#d08a00', approved: '#3a9a68', rejected: '#a61b1b', in_progress: '#1f4e8c', completed: '#7c4dbe', accepted: '#1d5e35',
    };
    const ngayNgan = (s) => { const [n, t, ng] = String(s || '').slice(0, 10).split('-'); return ng ? ng + '/' + t + '/' + n : ''; };

    function theSo(ten, giaTri, mau, tenIcon, lienKet, laChu) {
        const noiDung = [iconTron(tenIcon), tao('span', {},
            tao('span', { class: 'gia-tri' + (laChu ? ' chu' : ''), text: String(giaTri) }), tao('span', { class: 'nhan-so', text: ten }))];
        return lienKet
            ? tao('a', { class: 'the-so mau-' + mau, href: '#' + lienKet }, noiDung)
            : tao('div', { class: 'the-so mau-' + mau }, noiDung);
    }

    function loiChao() {
        const gio = new Date().getHours();
        const buoi = gio < 11 ? 'Chào buổi sáng' : (gio < 14 ? 'Chào buổi trưa' : (gio < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
        const ngay = new Date().toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' });
        return tao('section', { class: 'loi-chao', 'aria-label': 'Lời chào' },
            tao('div', {}, tao('h2', { text: buoi + ', ' + tenNguoiDung }), tao('p', { text: MO_TA_VAI_TRO[vaiTroChinh] || '' })),
            tao('p', { class: 'ngay', text: ngay.charAt(0).toUpperCase() + ngay.slice(1) }));
    }

    function lienKetXem(text, trang) {
        return tao('a', { class: 'lien-ket-xem', href: '#' + trang }, text, icon('mui-ten'));
    }

    function theTomTat(tieuDe, ...con) {
        return tao('section', { class: 'the' }, tao('h2', { text: tieuDe }), ...con);
    }

    /** Thanh ngang chia theo trang thai, kem chu giai co so luong */
    function thanhPhanBo(dem) {
        const tong = Object.values(dem).reduce((s, v) => s + v, 0);
        const co = Object.keys(MAU_TRANG_THAI).filter((tt) => dem[tt]);
        const moTa = co.map((tt) => TEN_TRANG_THAI[tt] + ' ' + dem[tt]).join(', ');
        return [
            tao('p', { class: 'phu', text: 'Tổng ' + tong + ' đề tài' }),
            tao('div', { class: 'thanh-phan-bo', role: 'img', 'aria-label': 'Phân bố đề tài theo trạng thái: ' + (moTa || 'chưa có') },
                co.map((tt) => tao('span', { style: 'width:' + (dem[tt] / tong * 100) + '%;background:' + MAU_TRANG_THAI[tt] }))),
            tao('div', { class: 'chu-giai' }, co.map((tt) => tao('span', {},
                tao('span', { class: 'o-mau', style: 'background:' + MAU_TRANG_THAI[tt] }), TEN_TRANG_THAI[tt] + ': ' + dem[tt]))),
        ];
    }

    async function theThongBaoGanDay() {
        const kq = await goi('GET', '/thong-bao?size=4');
        const ds = kq.ok ? kq.data.items : [];
        return theTomTat('Thông báo gần đây',
            ds.length ? tao('ul', { class: 'ds-goi-y' }, ds.map((tb) => tao('li', {}, iconTron('chuong'),
                tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: tb.tieu_de }), tao('span', { class: 'phu', text: thoiGian(tb.created_at) })))))
                : tao('p', { class: 'phu', text: 'Chưa có thông báo.' }),
            tao('div', { class: 'hang' }, lienKetXem('Xem tất cả thông báo', 'thong-bao')));
    }

    async function trangTongQuan() {
        const vung = khoi;
        vung.append(loiChao());
        const luoiSo = tao('div', { class: 'luoi-so' });
        const luoiHai = tao('div', { class: 'luoi-hai' });
        vung.append(luoiSo, luoiHai);
        const kqTb = await goi('GET', '/thong-bao?size=1');
        const chuaDoc = kqTb.ok ? Number(kqTb.data.chua_doc) || 0 : 0;

        if (vaiTroChinh === 'sinh_vien') await tongQuanSinhVien(luoiSo, luoiHai, chuaDoc);
        else if (vaiTroChinh === 'gvhd') await tongQuanGvhd(luoiSo, luoiHai, chuaDoc);
        else if (vaiTroChinh === 'thu_ky_khoa') await tongQuanThuKy(luoiSo, luoiHai);
        else if (vaiTroChinh === 'hoi_dong') await tongQuanHoiDong(luoiSo, luoiHai, chuaDoc);
        luoiHai.append(await theThongBaoGanDay());
    }

    async function tongQuanSinhVien(luoiSo, luoiHai, chuaDoc) {
        const kq = await goi('GET', '/de-tai?size=50');
        const ds = kq.ok ? kq.data.items : [];
        const dt = ds.find((d) => !['rejected', 'accepted'].includes(d.trang_thai)) || ds[0];
        let dongMoc = [];
        if (dt) {
            const [kqMoc, kqBc] = await Promise.all([
                goi('GET', '/moc-thoi-gian?lop_hoc_phan_id=' + dt.lop_hoc_phan_id),
                CO_BAO_CAO.includes(dt.trang_thai) ? goi('GET', '/de-tai/' + dt.id + '/bao-cao-moc') : Promise.resolve({ ok: true, data: [] }),
            ]);
            const bc = kqBc.ok ? kqBc.data : [];
            const bayGio = Date.now();
            dongMoc = (kqMoc.ok ? kqMoc.data : []).map((m) => {
                const ban = bc.filter((b) => String(b.moc_thoi_gian_id) === String(m.id));
                const quaHan = new Date(String(m.han_nop).replace(' ', 'T')).getTime() < bayGio;
                const tt = ban.some((b) => b.trang_thai === 'approved') ? 'approved' : (ban[0] ? ban[0].trang_thai : (quaHan ? 'qua-han' : 'chua-nop'));
                return { m, tt, quaHan };
            });
        }
        const batBuoc = dongMoc.filter((d) => Number(d.m.bat_buoc));
        const daDuyet = batBuoc.filter((d) => d.tt === 'approved').length;
        const mocTiep = dongMoc.filter((d) => !d.quaHan && d.tt !== 'approved')
            .sort((a, b) => String(a.m.han_nop).localeCompare(String(b.m.han_nop)))[0];

        luoiSo.append(
            theSo('Trạng thái đề tài', dt ? TEN_TRANG_THAI[dt.trang_thai] : 'Chưa có đề tài', 'xanh', 'de-tai', 'de-tai-cua-toi', true),
            theSo('Mốc bắt buộc đã duyệt', batBuoc.length ? daDuyet + '/' + batBuoc.length : '—', 'luc', 'nghiem-thu', 'de-tai-cua-toi'),
            theSo('Hạn nộp gần nhất', mocTiep ? ngayNgan(mocTiep.m.han_nop) : 'Không có', 'vang', 'lich', 'de-tai-cua-toi', true),
            theSo('Thông báo chưa đọc', chuaDoc, 'do', 'chuong', 'thong-bao'));

        const TEN_MOC = { 'qua-han': 'Quá hạn, chưa nộp', 'chua-nop': 'Chưa nộp' };
        luoiHai.append(dt
            ? theTomTat('Tiến độ đề tài',
                tao('div', { class: 'hang' }, tao('h3', { text: dt.ten_de_tai }), nhanTrangThai(dt.trang_thai)),
                thanhTienDo(dt.trang_thai),
                dongMoc.length ? [tao('h3', { class: 'tieu-de-phu', text: 'Các mốc nộp báo cáo' }),
                    tao('ul', { class: 'ds-moc' }, dongMoc.map((d) => tao('li', {},
                        tao('span', { class: 'cham cham-' + d.tt, 'aria-hidden': 'true' }),
                        tao('span', { class: 'ten-moc' }, d.m.ten_moc, tao('small', { text: 'Hạn ' + thoiGian(d.m.han_nop) + (Number(d.m.bat_buoc) ? '' : ' · tự chọn') })),
                        TEN_MOC[d.tt] ? tao('span', { class: 'phu', text: TEN_MOC[d.tt] }) : nhanTrangThai(d.tt))))] : null,
                tao('div', { class: 'hang' }, lienKetXem('Mở đề tài và nộp báo cáo', 'de-tai-cua-toi')))
            : theTomTat('Tiến độ đề tài', tao('p', { class: 'phu', text: 'Bạn chưa có đề tài nào.' }),
                tao('div', { class: 'hang' }, lienKetXem('Đề xuất đề tài', 'de-tai-cua-toi'))));
    }

    async function tongQuanGvhd(luoiSo, luoiHai, chuaDoc) {
        const kq = await goi('GET', '/de-tai?size=100');
        const ds = kq.ok ? kq.data.items : [];
        const dem = {};
        ds.forEach((d) => { dem[d.trang_thai] = (dem[d.trang_thai] || 0) + 1; });
        const choDuyet = ds.filter((d) => d.trang_thai === 'pending');
        luoiSo.append(
            theSo('Đề tài phụ trách', ds.length, 'xanh', 'de-tai', 'de-tai-phu-trach'),
            theSo('Chờ bạn duyệt', choDuyet.length, 'vang', 'canh-bao', 'de-tai-phu-trach'),
            theSo('Đang thực hiện', (dem.approved || 0) + (dem.in_progress || 0), 'xanh', 'thong-ke', 'de-tai-phu-trach'),
            theSo('Thông báo chưa đọc', chuaDoc, 'do', 'chuong', 'thong-bao'));
        luoiHai.append(theTomTat('Đề tài bạn hướng dẫn',
            ...thanhPhanBo(dem),
            tao('h3', { class: 'tieu-de-phu', text: 'Chờ bạn duyệt' }),
            choDuyet.length
                ? tao('ul', { class: 'ds-goi-y' }, choDuyet.slice(0, 5).map((d) => tao('li', {}, iconTron('de-tai'),
                    tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: d.ten_de_tai }), tao('span', { class: 'phu', text: 'Đề xuất ' + thoiGian(d.created_at) })))))
                : tao('p', { class: 'phu', text: 'Không có đề tài nào chờ duyệt.' }),
            tao('div', { class: 'hang' }, lienKetXem('Đi tới đề tài phụ trách', 'de-tai-phu-trach'))));
    }

    async function tongQuanThuKy(luoiSo, luoiHai) {
        const tongSo = async (url) => { const k = await goi('GET', url); return k.ok ? Number(k.data.total) || 0 : 0; };
        const dsTt = Object.keys(MAU_TRANG_THAI);
        const [soTk, ...soTt] = await Promise.all([tongSo('/nguoi-dung?size=1'), ...dsTt.map((tt) => tongSo('/de-tai?size=1&trang_thai=' + tt))]);
        const dem = Object.fromEntries(dsTt.map((tt, i) => [tt, soTt[i]]));
        const tong = soTt.reduce((s, v) => s + v, 0);
        luoiSo.append(
            theSo('Tổng số đề tài', tong, 'xanh', 'de-tai', 'tra-cuu'),
            theSo('Chờ phân công, duyệt', dem.pending, 'vang', 'nhom', 'phan-cong'),
            theSo('Chờ chốt nghiệm thu', dem.completed, 'luc', 'nghiem-thu', 'chot-nghiem-thu'),
            theSo('Tài khoản người dùng', soTk, 'xanh', 'tai-khoan', 'tai-khoan'));
        luoiHai.append(theTomTat('Đề tài toàn khoa', ...thanhPhanBo(dem),
            tao('h3', { class: 'tieu-de-phu', text: 'Việc cần xử lý' }),
            tao('ul', { class: 'ds-goi-y' },
                tao('li', {}, iconTron('nhom'), tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: dem.pending + ' đề tài chờ duyệt' }), tao('span', { class: 'phu', text: 'Kiểm tra và phân công giảng viên hướng dẫn' })), lienKetXem('Mở', 'phan-cong')),
                tao('li', {}, iconTron('nghiem-thu'), tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: dem.completed + ' đề tài đủ điều kiện nghiệm thu' }), tao('span', { class: 'phu', text: 'Chốt kết quả sau khi hội đồng chấm' })), lienKetXem('Mở', 'chot-nghiem-thu')),
                tao('li', {}, iconTron('lich'), tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: 'Lớp học phần và mốc thời gian' }), tao('span', { class: 'phu', text: 'Thêm lớp, đặt và gia hạn mốc nộp' })), lienKetXem('Mở', 'lop-moc')))));
    }

    async function tongQuanHoiDong(luoiSo, luoiHai, chuaDoc) {
        const [kqCho, kqXong] = await Promise.all([
            goi('GET', '/nghiem-thu/ho-so?size=100&trang_thai=completed'), goi('GET', '/nghiem-thu/ho-so?size=100&trang_thai=accepted')]);
        const cho = kqCho.ok ? kqCho.data.items : [];
        const chuaCham = cho.filter((d) => !d.toi_da_cham);
        luoiSo.append(
            theSo('Hồ sơ chờ chấm', cho.length, 'vang', 'nghiem-thu', 'ho-so-nghiem-thu'),
            theSo('Bạn chưa chấm', chuaCham.length, 'do', 'canh-bao', 'ho-so-nghiem-thu'),
            theSo('Đã nghiệm thu', kqXong.ok ? kqXong.data.items.length : 0, 'luc', 'de-tai', 'ho-so-nghiem-thu'),
            theSo('Thông báo chưa đọc', chuaDoc, 'xanh', 'chuong', 'thong-bao'));
        luoiHai.append(theTomTat('Hồ sơ bạn chưa chấm',
            chuaCham.length
                ? tao('ul', { class: 'ds-goi-y' }, chuaCham.slice(0, 6).map((d) => tao('li', {}, iconTron('de-tai'),
                    tao('div', { class: 'noi-dung-muc' }, tao('strong', { text: d.ten_de_tai }),
                        tao('span', { class: 'phu', text: d.so_luot_cham ? d.so_luot_cham + ' lượt chấm, điểm TB ' + d.diem_trung_binh : 'Chưa có lượt chấm' })))))
                : tao('p', { class: 'phu', text: 'Bạn đã chấm hết hồ sơ đang chờ.' }),
            tao('div', { class: 'hang' }, lienKetXem('Đi tới hồ sơ nghiệm thu', 'ho-so-nghiem-thu'))));
    }

    // ---------- Menu va chuyen trang (theo dau # tren dia chi) ----------
    const dsTrang = [];
    function themTrang(khoa, ten, tenIcon, nhom, dung) { dsTrang.push({ khoa, ten, icon: tenIcon, nhom, dung, el: null, daDung: false }); }

    function dangKyTrang() {
        const coVaiTro = vaiTro.some((v) => THU_TU_VAI_TRO.includes(v));
        themTrang('tong-quan', 'Tổng quan', 'tong-quan', 'Chung', trangTongQuan);
        themTrang('thong-bao', 'Thông báo', 'chuong', 'Chung', null);
        if (coVaiTro) themTrang('tra-cuu', 'Tra cứu đề tài', 'tim-kiem', 'Chung', khoiTimKiemDeTai);
        if (vaiTro.includes('thu_ky_khoa')) {
            themTrang('phan-cong', 'Phân công GVHD', 'nhom', 'Thư ký khoa', khoiPhanCong);
            themTrang('chot-nghiem-thu', 'Chốt nghiệm thu', 'nghiem-thu', 'Thư ký khoa', khoiChotNghiemThu);
            themTrang('lop-moc', 'Lớp học phần và mốc', 'lich', 'Thư ký khoa', khoiLopVaMoc);
            themTrang('tai-khoan', 'Tài khoản người dùng', 'tai-khoan', 'Thư ký khoa', khoiTaiKhoan);
            themTrang('nhat-ky', 'Nhật ký hệ thống', 'nhat-ky', 'Thư ký khoa', khoiNhatKyHeThong);
            themTrang('thong-ke', 'Báo cáo thống kê', 'thong-ke', 'Thư ký khoa', () => khoiBaoCao(true));
        }
        if (vaiTro.includes('gvhd')) {
            themTrang('de-tai-phu-trach', 'Đề tài tôi phụ trách', 'de-tai', 'Giảng viên hướng dẫn', khoiGvhd);
            if (!vaiTro.includes('thu_ky_khoa')) themTrang('thong-ke', 'Báo cáo thống kê', 'thong-ke', 'Giảng viên hướng dẫn', () => khoiBaoCao(false));
        }
        if (vaiTro.includes('sinh_vien')) themTrang('de-tai-cua-toi', 'Đề tài của tôi', 'de-tai', 'Sinh viên', khoiSinhVien);
        if (vaiTro.includes('hoi_dong')) themTrang('ho-so-nghiem-thu', 'Hồ sơ nghiệm thu', 'nghiem-thu', 'Hội đồng nghiệm thu', khoiHoiDong);
        themTrang('ho-so', 'Hồ sơ cá nhân', 'ho-so', 'Cá nhân', null);
    }

    function dungMenu() {
        const menu = document.getElementById('menu');
        let nhom = '';
        dsTrang.forEach((t) => {
            if (t.nhom !== nhom) { nhom = t.nhom; menu.append(tao('p', { class: 'nhom-menu', text: nhom })); }
            menu.append(tao('a', { class: 'muc-menu', href: '#' + t.khoa }, icon(t.icon), tao('span', { text: t.ten }),
                t.khoa === 'thong-bao' ? tao('span', { class: 'dem', hidden: true }) : null));
        });
    }

    const thanhBen = document.getElementById('thanh-ben');
    const lopPhu = document.getElementById('lop-phu');
    const nutMenu = document.getElementById('nut-menu');
    function dongMenu() {
        thanhBen.classList.remove('mo');
        lopPhu.hidden = true;
        nutMenu.setAttribute('aria-expanded', 'false');
    }
    function ganMenuDiDong() {
        nutMenu.addEventListener('click', () => {
            const mo = !thanhBen.classList.contains('mo');
            thanhBen.classList.toggle('mo', mo);
            lopPhu.hidden = !mo;
            nutMenu.setAttribute('aria-expanded', String(mo));
            if (mo) { const dau = thanhBen.querySelector('.muc-menu'); if (dau) dau.focus(); }
        });
        lopPhu.addEventListener('click', dongMenu);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && thanhBen.classList.contains('mo')) { dongMenu(); nutMenu.focus(); } });
    }

    async function moTrang(khoa, chuyenTieuDiem) {
        const t = dsTrang.find((x) => x.khoa === khoa) || dsTrang[0];
        if (!t.el) {
            t.el = document.getElementById('trang-' + t.khoa) || tao('div', { class: 'trang', id: 'trang-' + t.khoa });
            if (!t.el.parentNode) khoiVaiTro.append(t.el);
        }
        dsTrang.forEach((x) => { if (x.el) x.el.hidden = x !== t; });
        document.querySelectorAll('#menu .muc-menu').forEach((a) => {
            if (a.getAttribute('href') === '#' + t.khoa) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
        });
        document.getElementById('tieu-de-trang').textContent = t.ten;
        document.getElementById('duong-dan').textContent = t.nhom === 'Chung' ? 'Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu' : t.nhom;
        document.title = t.ten + ' – Hệ thống Quản lý Đồ án và Đề tài Nghiên cứu';
        dongMenu();
        if (chuyenTieuDiem) { window.scrollTo(0, 0); document.getElementById('tieu-de-trang').focus(); }
        if (!t.daDung && t.dung) {
            t.daDung = true;
            khoi = t.el;
            tenTrangDangDung = t.ten;
            try { await t.dung(); } catch (e) { t.el.append(tao('p', { class: 'loi', text: 'Có lỗi khi tải trang, vui lòng tải lại.' })); }
        }
    }

    function chuVietTat(ten) {
        const chu = ten.replace(/\(.*?\)/g, ' ').trim().split(/\s+/).filter(Boolean);
        return ((chu[0] || '')[0] || '') + (chu.length > 1 ? chu[chu.length - 1][0] : '');
    }

    async function khoiDong() {
        document.querySelectorAll('[data-icon]').forEach((o) => o.replaceChildren(icon(o.dataset.icon)));
        const vietTat = chuVietTat(tenNguoiDung).toUpperCase();
        document.getElementById('chu-viet-tat').textContent = vietTat;
        document.getElementById('chu-viet-tat-lon').textContent = vietTat;
        ganHoSo();
        ganMenuDiDong();
        dangKyTrang();
        dungMenu();
        window.addEventListener('hashchange', () => moTrang(window.location.hash.slice(1), true));
        await moTrang(window.location.hash.slice(1) || 'tong-quan', false);
        await taiThongBao();
    }

    khoiDong().catch(() => {
        document.getElementById('trang-thai-chung').textContent = 'Có lỗi khi tải trang, vui lòng tải lại.';
    });
})();
