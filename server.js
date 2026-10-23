const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const express = require('express');
const cookieParser = require('cookie-parser');
const jwt = require('jsonwebtoken');
const { Pool } = require('pg');
const sanitizeHtml = require('sanitize-html');

function loadLocalEnvironment() {
  const envPath = path.join(__dirname, '.env');
  if (!fs.existsSync(envPath)) return;

  for (const line of fs.readFileSync(envPath, 'utf8').split(/\r?\n/)) {
    const match = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$/);
    if (!match || process.env[match[1]] !== undefined) continue;

    let value = match[2];
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    process.env[match[1]] = value;
  }
}

loadLocalEnvironment();

const app = express();
const port = Number(process.env.PORT || 3000);
const isProduction = process.env.NODE_ENV === 'production';
const appName = 'Portal Informasi Peserta';
const sessionSecret = process.env.SESSION_SECRET;
const departments = [
  'Human Resources', 'Finance & Accounting', 'Marketing & Sales',
  'Information Technology', 'Operations', 'Research & Development',
  'Legal & Compliance', 'Customer Service', 'General Affairs',
  'Procurement', 'Quality Assurance', 'Public Relations', 'Lainnya',
];

if (!sessionSecret && isProduction) {
  throw new Error('SESSION_SECRET wajib diatur di environment production.');
}

const pool = new Pool({
  connectionString: process.env.SUPABASE_DB_URL,
  ssl: process.env.SUPABASE_DB_URL ? { rejectUnauthorized: false } : undefined,
  max: 1,
  idleTimeoutMillis: 10_000,
});

app.disable('x-powered-by');
app.use(express.urlencoded({ extended: false, limit: '100kb' }));
app.use(cookieParser());
app.use((req, res, next) => {
  res.set({
    'X-Content-Type-Options': 'nosniff',
    'X-Frame-Options': 'SAMEORIGIN',
    'Referrer-Policy': 'strict-origin-when-cross-origin',
  });
  next();
});
app.get('/style.css', (req, res) => res.sendFile(path.join(__dirname, 'style.css')));
app.get('/script.js', (req, res) => res.sendFile(path.join(__dirname, 'script.js')));

function e(value = '') {
  return String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[character]));
}

function icon(name, size = 20) {
  const paths = {
    compass: '<circle cx="12" cy="12" r="10"/><polygon points="16 8 14 14 8 16 10 10 16 8"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    map: '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
    bag: '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
    info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
  };
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths[name] || paths.info}</svg>`;
}

function layout(title, content, scripts = '') {
  return `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0f2140"><title>${e(title)} | ${appName}</title><link rel="stylesheet" href="/style.css"></head><body>${content}${scripts}</body></html>`;
}

function footer() {
  return `<footer class="footer"><p>&copy; ${new Date().getFullYear()} ${appName}. Seluruh hak cipta dilindungi.</p></footer>`;
}

function csrfToken(req, res) {
  let token = req.cookies.csrf_token;
  if (!token) {
    token = crypto.randomBytes(32).toString('hex');
    res.cookie('csrf_token', token, { httpOnly: false, sameSite: 'lax', secure: isProduction, maxAge: 7 * 24 * 60 * 60 * 1000 });
  }
  return token;
}

function csrfValid(req) {
  const cookie = req.cookies.csrf_token || '';
  const form = req.body.csrf_token || '';
  return cookie.length === form.length && cookie.length > 0 && crypto.timingSafeEqual(Buffer.from(cookie), Buffer.from(form));
}

function setSession(res, payload, expiresIn) {
  const token = jwt.sign(payload, sessionSecret || 'development-only-secret', { expiresIn });
  res.cookie('portal_session', token, { httpOnly: true, sameSite: 'lax', secure: isProduction, maxAge: expiresIn * 1000, path: '/' });
}

function session(req) {
  try {
    return jwt.verify(req.cookies.portal_session || '', sessionSecret || 'development-only-secret');
  } catch {
    return null;
  }
}

function clearSession(res) {
  res.clearCookie('portal_session', { httpOnly: true, sameSite: 'lax', secure: isProduction, path: '/' });
}

function normalizePhone(input) {
  let phone = String(input || '').replace(/[\s\-.()]/g, '');
  if (phone.startsWith('+62')) phone = `0${phone.slice(3)}`;
  else if (phone.startsWith('62') && phone.length > 10) phone = `0${phone.slice(2)}`;
  else if (!phone.startsWith('0')) phone = `0${phone}`;
  return phone;
}

function safeContent(content) {
  return sanitizeHtml(content, {
    allowedTags: ['p', 'strong', 'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a', 'div', 'br'],
    allowedAttributes: { a: ['href'], div: ['class'], table: ['class'], ul: ['class'] },
    allowedSchemes: ['http', 'https', 'mailto', 'tel'],
  });
}

async function seedInformation() {
  const { rows } = await pool.query('SELECT COUNT(*)::int AS count FROM informasi');
  if (rows[0].count > 0) return;
  const items = [
    [1, 'jadwal', 'Jadwal Keberangkatan', 'clock', '<p><strong>Hari/Tanggal:</strong> Sabtu, 15 November 2026</p><p><strong>Waktu Berkumpul:</strong> Pukul 05.30 WIB</p><p><strong>Estimasi Berangkat:</strong> Pukul 06.00 WIB</p><p><strong>Titik Kumpul:</strong> Lobby Utama Gedung A</p>'],
    [2, 'rute', 'Rute Perjalanan', 'map', '<p>Perjalanan menuju <strong>Taman Wisata Alam Gunung Pancar, Bogor</strong>.</p><p><strong>Estimasi Waktu Tempuh:</strong> ± 2 jam 30 menit</p><p><strong>Rute:</strong> Jakarta → Tol Jagorawi → Exit Sentul → Gunung Pancar</p>'],
    [3, 'jadwal', 'Susunan Acara', 'calendar', '<table class="schedule-table"><thead><tr><th>Waktu</th><th>Kegiatan</th></tr></thead><tbody><tr><td>05.30 – 06.00</td><td>Registrasi &amp; Persiapan</td></tr><tr><td>06.00 – 08.30</td><td>Perjalanan Menuju Lokasi</td></tr><tr><td>09.00 – 12.00</td><td>Kegiatan Outbound &amp; Team Building</td></tr><tr><td>16.00 – 18.30</td><td>Perjalanan Kembali</td></tr></tbody></table>'],
    [4, 'persiapan', 'Perlengkapan yang Dibawa', 'bag', '<ul class="checklist"><li>Pakaian olahraga yang nyaman</li><li>Sepatu olahraga atau sneakers</li><li>Jaket tipis atau jas hujan</li><li>Obat-obatan pribadi</li><li>Botol minum tumbler</li><li>Power bank dan kabel charger</li></ul>'],
    [5, 'peraturan', 'Peraturan Peserta', 'shield', '<ol><li>Mematuhi instruksi panitia selama kegiatan.</li><li>Tidak meninggalkan area kegiatan tanpa izin.</li><li>Menjaga kebersihan dan ketertiban lokasi.</li><li>Melaporkan kondisi kesehatan khusus kepada panitia.</li></ol>'],
    [6, 'kontak', 'Kontak Darurat & Panitia', 'phone', '<p>Hubungi koordinator lapangan atau tim medis yang ditetapkan panitia jika terjadi keadaan darurat.</p>'],
  ];
  for (const item of items) {
    await pool.query('INSERT INTO informasi (urutan, kategori, judul, ikon, konten) VALUES ($1, $2, $3, $4, $5)', item);
  }
}

async function participant(req) {
  const current = session(req);
  if (!current || current.role !== 'participant') return null;
  const { rows } = await pool.query('SELECT id, nama_lengkap, departemen, token FROM peserta WHERE id = $1 AND token = $2', [current.sub, current.token]);
  return rows[0] || null;
}

function header(subtitle, logout = false) {
  return `<header class="header"><div class="header-inner"><div class="header-brand"><div class="header-logo">${icon('compass', 22)}</div><div><div class="header-title">${appName}</div><div class="header-subtitle">${e(subtitle)}</div></div></div>${logout ? `<a class="btn-logout" href="/logout">${icon('logout', 16)}<span>Keluar</span></a>` : ''}</div></header>`;
}

function registerPage(req, res, errors = [], old = {}) {
  const token = csrfToken(req, res);
  const options = departments.map((department) => `<option value="${e(department)}" ${old.departemen === department ? 'selected' : ''}>${e(department)}</option>`).join('');
  const errorBlock = errors.length ? `<div class="alert alert-error" role="alert"><span class="alert-icon">${icon('info', 18)}</span><div>${errors.map((error) => `<div>${e(error)}</div>`).join('')}</div></div>` : '';
  const content = `<div class="page-wrapper"><section class="hero"><div class="hero-logo">${icon('compass', 32)}</div><h1>Selamat Datang, Peserta!</h1><p>Daftarkan diri Anda untuk mengakses informasi lengkap perjalanan kegiatan.</p></section><section class="form-section"><div class="form-card"><h2 class="form-title">Registrasi Peserta</h2><p class="form-desc">Lengkapi data berikut untuk melanjutkan ke portal informasi.</p>${errorBlock}<form id="registerForm" method="post" action="/register" novalidate><input type="hidden" name="csrf_token" value="${e(token)}"><div class="form-group"><label class="form-label" for="nama">Nama Lengkap <span class="required">*</span></label><input class="form-input" id="nama" name="nama" value="${e(old.nama)}" maxlength="150" autocomplete="name" required><div class="field-error" id="namaError"></div></div><div class="form-group"><label class="form-label" for="departemen">Departemen <span class="required">*</span></label><select class="form-select" id="departemen" name="departemen" required><option value="">Pilih Departemen</option>${options}</select><div class="field-error" id="departemenError"></div></div><div class="form-group"><label class="form-label" for="no_hp">Nomor HP <span class="required">*</span></label><div class="phone-input-group"><span class="phone-prefix">+62</span><input class="form-input" id="no_hp" name="no_hp" value="${e(String(old.no_hp || '').replace(/^0/, ''))}" maxlength="15" inputmode="tel" autocomplete="tel" required></div><div class="form-hint">Format: 8xxxxxxxxxx (tanpa angka 0 di depan)</div><div class="field-error" id="no_hpError"></div></div><div class="form-group"><div class="form-check"><input id="consent" type="checkbox" name="consent" value="yes" ${old.consent ? 'checked' : ''} required><label class="form-check-label" for="consent">Saya menyetujui pemrosesan data pribadi sesuai <a href="/privacy" target="_blank" rel="noopener">Kebijakan Privasi</a>.</label></div><div class="field-error" id="consentError"></div></div><button class="btn btn-primary" id="submitBtn" type="submit"><span class="btn-text">Daftar dan Lanjutkan</span></button></form></div></section>${footer()}</div>`;
  res.status(errors.length ? 422 : 200).send(layout('Registrasi', content, '<script src="/script.js"></script>'));
}

app.get(['/', '/register'], async (req, res, next) => {
  try {
    if (await participant(req)) return res.redirect('/info');
    return registerPage(req, res);
  } catch (error) { next(error); }
});

app.post('/register', async (req, res, next) => {
  try {
    const old = { nama: String(req.body.nama || '').trim(), departemen: String(req.body.departemen || '').trim(), no_hp: normalizePhone(req.body.no_hp), consent: req.body.consent === 'yes' };
    const errors = [];
    if (!csrfValid(req)) errors.push('Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.');
    if (old.nama.length < 3 || old.nama.length > 150 || !/^[a-zA-Z\s'.,-]+$/.test(old.nama)) errors.push('Nama lengkap harus 3–150 karakter dan hanya berisi huruf atau spasi.');
    if (!departments.includes(old.departemen)) errors.push('Silakan pilih departemen yang valid.');
    if (!/^0\d{9,13}$/.test(old.no_hp)) errors.push('Format nomor HP tidak valid. Gunakan format 08xxxxxxxxx.');
    if (!old.consent) errors.push('Anda harus menyetujui pemrosesan data pribadi.');
    if (errors.length) return registerPage(req, res, errors, old);
    await seedInformation();
    const existing = await pool.query('SELECT id, token FROM peserta WHERE no_hp = $1 AND nama_lengkap = $2 LIMIT 1', [old.no_hp, old.nama]);
    let participantData;
    if (existing.rows[0]) {
      participantData = existing.rows[0];
      await pool.query('UPDATE peserta SET last_access = NOW() WHERE id = $1', [participantData.id]);
    } else {
      const token = crypto.randomBytes(32).toString('hex');
      const inserted = await pool.query('INSERT INTO peserta (nama_lengkap, departemen, no_hp, token, last_access) VALUES ($1, $2, $3, $4, NOW()) RETURNING id, token', [old.nama, old.departemen, old.no_hp, token]);
      participantData = inserted.rows[0];
    }
    setSession(res, { sub: participantData.id, token: participantData.token, role: 'participant' }, 7 * 24 * 60 * 60);
    return res.redirect(303, '/info');
  } catch (error) { next(error); }
});

app.get('/info', async (req, res, next) => {
  try {
    const user = await participant(req);
    if (!user) return res.redirect('/register');
    await seedInformation();
    await pool.query('UPDATE peserta SET last_access = NOW() WHERE id = $1', [user.id]);
    const { rows } = await pool.query('SELECT judul, ikon, konten, kategori FROM informasi WHERE aktif = TRUE ORDER BY urutan, id');
    const labels = { jadwal: 'Jadwal & Acara', rute: 'Rute Perjalanan', persiapan: 'Persiapan', peraturan: 'Peraturan', kontak: 'Kontak', umum: 'Informasi Umum' };
    const groups = rows.reduce((result, item) => { (result[item.kategori] ||= []).push(item); return result; }, {});
    let index = 0;
    const cards = Object.entries(groups).map(([category, items]) => `<div class="section-title">${e(labels[category] || category)}</div>${items.map((item) => { const current = index++; return `<article class="info-card${current === 0 ? ' is-open' : ''}"><button class="info-card-header" type="button" aria-expanded="${current === 0}" aria-controls="card-${current}"><span class="info-card-icon">${icon(item.ikon, 20)}</span><span class="info-card-title">${e(item.judul)}</span><span class="info-card-chevron">⌄</span></button><div class="info-card-body" id="card-${current}"><div class="info-card-content">${safeContent(item.konten)}</div></div></article>`; }).join('')}`).join('');
    const content = `<div class="page-wrapper">${header('Informasi Kegiatan', true)}<section class="welcome-banner"><div class="welcome-content"><div class="welcome-greeting">Halo,</div><div class="welcome-name">${e(user.nama_lengkap)}</div><div class="welcome-dept"><span class="welcome-dept-badge">${e(user.departemen)}</span></div></div></section><main class="info-section">${cards || '<div class="alert alert-warning">Informasi perjalanan belum tersedia. Silakan periksa kembali nanti.</div>'}</main>${footer()}</div>`;
    res.send(layout('Informasi Perjalanan', content, '<script src="/script.js"></script>'));
  } catch (error) { next(error); }
});

app.get('/privacy', (req, res) => {
  const content = `<div class="page-wrapper">${header('Kebijakan Privasi')}<main class="privacy-page"><a href="/register" class="back-link">Kembali</a><h1>Kebijakan Privasi</h1><p>Terakhir diperbarui: ${new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date())}</p><h2>1. Data yang Kami Kumpulkan</h2><p>Kami mengumpulkan nama lengkap, departemen, dan nomor telepon peserta.</p><h2>2. Tujuan Pengumpulan Data</h2><p>Data dipakai untuk identifikasi peserta, pemberian akses informasi perjalanan, serta koordinasi kegiatan.</p><h2>3. Penyimpanan dan Perlindungan Data</h2><p>Data disimpan di Supabase dengan akses terbatas untuk aplikasi dan panitia. Peserta dapat meminta koreksi atau penghapusan data kepada panitia.</p></main>${footer()}</div>`;
  res.send(layout('Kebijakan Privasi', content));
});

app.get('/logout', (req, res) => { clearSession(res); res.redirect('/register'); });

function adminSession(req) {
  const current = session(req);
  return current?.role === 'admin' ? current : null;
}

function adminLogin(req, res, error = '') {
  const token = csrfToken(req, res);
  const errorBlock = error ? `<div class="alert alert-error">${e(error)}</div>` : '';
  const content = `<div class="page-wrapper"><section class="hero"><div class="hero-logo">${icon('shield', 32)}</div><h1>Admin Panel</h1><p>Masuk untuk mengelola portal.</p></section><section class="form-section"><div class="form-card">${errorBlock}<form method="post" action="/admin/login"><input type="hidden" name="csrf_token" value="${e(token)}"><div class="form-group"><label class="form-label" for="username">Username</label><input class="form-input" id="username" name="username" autocomplete="username" required></div><div class="form-group"><label class="form-label" for="password">Password</label><input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required></div><button class="btn btn-primary" type="submit">Masuk</button></form></div></section></div>`;
  res.status(error ? 401 : 200).send(layout('Admin', content));
}

app.get('/admin', async (req, res, next) => {
  try {
    if (!adminSession(req)) return adminLogin(req, res);
    const token = csrfToken(req, res);
    const [participants, information] = await Promise.all([
      pool.query('SELECT nama_lengkap, departemen, no_hp, created_at FROM peserta ORDER BY created_at DESC'),
      pool.query('SELECT id, urutan, judul, kategori, aktif FROM informasi ORDER BY urutan, id'),
    ]);
    const participantRows = participants.rows.map((row, index) => `<tr><td>${index + 1}</td><td><strong>${e(row.nama_lengkap)}</strong></td><td>${e(row.departemen)}</td><td>${e(row.no_hp)}</td><td>${new Intl.DateTimeFormat('id-ID', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(row.created_at))}</td></tr>`).join('') || '<tr><td colspan="5">Belum ada peserta terdaftar.</td></tr>';
    const infoRows = information.rows.map((row) => `<tr><td>${row.urutan}</td><td><strong>${e(row.judul)}</strong></td><td>${e(row.kategori)}</td><td>${row.aktif ? 'Aktif' : 'Nonaktif'}</td><td><form method="post" action="/admin" class="inline-form"><input type="hidden" name="csrf_token" value="${e(token)}"><input type="hidden" name="id" value="${row.id}"><button class="btn-sm" name="action" value="toggle">Ubah status</button><button class="btn-sm btn-danger" name="action" value="delete" onclick="return confirm('Hapus informasi ini?')">Hapus</button></form></td></tr>`).join('');
    const content = `<div class="page-wrapper">${header('Admin Panel', true)}<main class="info-section"><div class="alert alert-success">Peserta terdaftar: ${participants.rows.length}. Item informasi: ${information.rows.length}.</div><div class="admin-section"><div class="admin-section-header"><strong>Daftar Peserta</strong></div><div class="overflow-x"><table class="admin-table"><thead><tr><th>#</th><th>Nama</th><th>Departemen</th><th>No. HP</th><th>Terdaftar</th></tr></thead><tbody>${participantRows}</tbody></table></div></div><div class="admin-section"><div class="admin-section-header"><strong>Kelola Informasi</strong></div><div class="overflow-x"><table class="admin-table"><thead><tr><th>Urutan</th><th>Judul</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${infoRows}</tbody></table></div></div><div class="admin-section"><div class="admin-section-header"><strong>Tambah Informasi</strong></div><div class="admin-section-body"><form method="post" action="/admin"><input type="hidden" name="csrf_token" value="${e(token)}"><input type="hidden" name="action" value="add"><div class="form-group"><label class="form-label" for="judul">Judul</label><input class="form-input" id="judul" name="judul" maxlength="200" required></div><div class="form-group"><label class="form-label" for="kategori">Kategori</label><select class="form-select" id="kategori" name="kategori"><option value="umum">Umum</option><option value="jadwal">Jadwal</option><option value="rute">Rute</option><option value="persiapan">Persiapan</option><option value="peraturan">Peraturan</option><option value="kontak">Kontak</option></select></div><div class="form-group"><label class="form-label" for="konten">Konten</label><textarea class="form-input" id="konten" name="konten" rows="6" required></textarea></div><button class="btn btn-primary" type="submit">Tambah Informasi</button></form></div></div></main>${footer()}</div>`;
    res.send(layout('Admin', content));
  } catch (error) { next(error); }
});

app.post('/admin/login', (req, res) => {
  if (!csrfValid(req)) return adminLogin(req, res, 'Sesi tidak valid. Silakan muat ulang halaman.');
  if (!process.env.ADMIN_USER || !process.env.ADMIN_PASSWORD || req.body.username !== process.env.ADMIN_USER || req.body.password !== process.env.ADMIN_PASSWORD) return adminLogin(req, res, 'Username atau password salah.');
  setSession(res, { role: 'admin' }, 12 * 60 * 60);
  return res.redirect(303, '/admin');
});

app.post('/admin', async (req, res, next) => {
  try {
    if (!adminSession(req)) return res.redirect('/admin');
    if (!csrfValid(req)) return res.status(403).send(layout('Akses ditolak', '<main class="info-section"><div class="alert alert-error">Sesi tidak valid. Muat ulang halaman dan coba lagi.</div></main>'));
    const id = Number(req.body.id);
    if (req.body.action === 'toggle' && Number.isSafeInteger(id)) await pool.query('UPDATE informasi SET aktif = NOT aktif WHERE id = $1', [id]);
    if (req.body.action === 'delete' && Number.isSafeInteger(id)) await pool.query('DELETE FROM informasi WHERE id = $1', [id]);
    if (req.body.action === 'add') {
      const judul = String(req.body.judul || '').trim();
      const kategori = String(req.body.kategori || 'umum').trim();
      const konten = safeContent(String(req.body.konten || ''));
      if (judul && konten && ['umum', 'jadwal', 'rute', 'persiapan', 'peraturan', 'kontak'].includes(kategori)) {
        await pool.query('INSERT INTO informasi (judul, kategori, ikon, konten, aktif) VALUES ($1, $2, $3, $4, TRUE)', [judul, kategori, 'info', konten]);
      }
    }
    res.redirect(303, '/admin');
  } catch (error) { next(error); }
});

app.use((req, res) => res.status(404).send(layout('Halaman tidak ditemukan', '<main class="info-section"><div class="alert alert-warning">Halaman yang diminta tidak tersedia.</div></main>')));
app.use((error, req, res, next) => {
  console.error(error);
  res.status(500).send(layout('Gangguan sistem', '<main class="info-section"><div class="alert alert-error">Terjadi gangguan sistem. Silakan coba lagi beberapa saat.</div></main>'));
});

if (require.main === module) {
  app.listen(port, () => console.log(`Portal berjalan di http://localhost:${port}`));
}

module.exports = app;
