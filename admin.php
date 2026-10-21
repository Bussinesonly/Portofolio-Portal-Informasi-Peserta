<?php
/**
 * Panel Admin Sederhana
 * Portal Informasi Peserta
 * 
 * Halaman ini dilindungi oleh autentikasi dasar.
 * Ubah kredensial di bawah sebelum deploy.
 */

// ─── Kredensial Admin (UBAH SEBELUM DEPLOY!) ─────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

initSession();

// ─── Autentikasi Admin ──────────────────────────────
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';
        
        if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
            $_SESSION['admin_auth'] = true;
            session_regenerate_id(true);
            redirect('admin.php');
        } else {
            $loginError = 'Username atau password salah.';
        }
    }
    
    if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
        showAdminLogin($loginError ?? '');
        exit;
    }
}

$pdo = getDB();
$message = '';
$messageType = '';

// ─── Handle Actions ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'logout_admin') {
        unset($_SESSION['admin_auth']);
        redirect('admin.php');
    }
    
    if ($action === 'add_info') {
        $judul    = trim($_POST['judul'] ?? '');
        $konten   = trim($_POST['konten'] ?? '');
        $kategori = trim($_POST['kategori'] ?? 'umum');
        $ikon     = trim($_POST['ikon'] ?? 'info');
        $urutan   = (int) ($_POST['urutan'] ?? 0);
        
        if ($judul && $konten) {
            $stmt = $pdo->prepare("
                INSERT INTO informasi (judul, konten, kategori, ikon, urutan, aktif)
                VALUES (:judul, :konten, :kategori, :ikon, :urutan, 1)
            ");
            $stmt->execute([
                'judul'    => $judul,
                'konten'   => $konten,
                'kategori' => $kategori,
                'ikon'     => $ikon,
                'urutan'   => $urutan,
            ]);
            $message = 'Informasi berhasil ditambahkan.';
            $messageType = 'success';
        }
    }
    
    if ($action === 'toggle_info') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE informasi SET aktif = NOT aktif WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Status informasi diperbarui.';
        $messageType = 'success';
    }
    
    if ($action === 'delete_info') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM informasi WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Informasi berhasil dihapus.';
        $messageType = 'success';
    }
}

// ─── Ambil Data ──────────────────────────────────────
$pesertaList = $pdo->query("SELECT * FROM peserta ORDER BY created_at DESC")->fetchAll();
$infoList = $pdo->query("SELECT * FROM informasi ORDER BY urutan ASC, id ASC")->fetchAll();
$totalPeserta = count($pesertaList);
$totalInfo = count($infoList);

function showAdminLogin(string $error = ''): void
{
    require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= APP_VERSION ?>">
</head>
<body>
    <div class="page-wrapper">
        <section class="hero">
            <div class="hero-logo"><?= icon('shield', 32) ?></div>
            <h1>Admin Panel</h1>
            <p>Masuk untuk mengelola portal.</p>
        </section>
        <section class="form-section">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon"><?= icon('alert', 18) ?></span>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="admin_login" value="1">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary"><span class="btn-text">Masuk</span></button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
<?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Panel — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= APP_VERSION ?>">
    <style>
        .admin-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .stat-card { background: var(--white); padding: 18px; border-radius: var(--radius-md); box-shadow: var(--shadow-card); text-align: center; }
        .stat-number { font-size: 1.75rem; font-weight: 700; color: var(--navy-800); }
        .stat-label { font-size: 0.78rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }
        .admin-section { background: var(--white); border-radius: var(--radius-md); box-shadow: var(--shadow-card); margin-bottom: 16px; overflow: hidden; }
        .admin-section-header { padding: 16px 18px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; }
        .admin-section-title { font-size: 0.95rem; font-weight: 600; color: var(--navy-800); }
        .admin-section-body { padding: 18px; }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        .admin-table th { background: var(--gray-50); padding: 10px 12px; text-align: left; font-weight: 600; color: var(--gray-600); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--gray-200); }
        .admin-table td { padding: 10px 12px; border-bottom: 1px solid var(--gray-100); vertical-align: top; }
        .admin-table tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; }
        .badge-active { background: var(--success-bg); color: var(--success); }
        .badge-inactive { background: var(--gray-100); color: var(--gray-500); }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; border-radius: 6px; border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-600); cursor: pointer; font-family: inherit; font-weight: 500; transition: all 0.15s; }
        .btn-sm:hover { background: var(--gray-50); border-color: var(--gray-300); }
        .btn-sm.btn-danger { color: var(--error); border-color: rgba(220, 38, 38, 0.2); }
        .btn-sm.btn-danger:hover { background: var(--error-bg); }
        .actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }
        .admin-form textarea { width: 100%; min-height: 120px; padding: 12px; font-family: 'Consolas', 'Monaco', monospace; font-size: 0.85rem; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm); background: var(--gray-50); resize: vertical; }
        .admin-form textarea:focus { border-color: var(--blue-400); outline: none; box-shadow: 0 0 0 3px rgba(74,158,255,0.12); background: var(--white); }
        .admin-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .admin-form .form-row { grid-template-columns: 1fr; } .admin-table { font-size: 0.72rem; } .admin-table th, .admin-table td { padding: 8px 8px; } }
        .overflow-x { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="header-inner">
                <div class="header-brand">
                    <div class="header-logo"><?= icon('shield', 22) ?></div>
                    <div>
                        <div class="header-title">Admin Panel</div>
                        <div class="header-subtitle"><?= e(APP_NAME) ?></div>
                    </div>
                </div>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="logout_admin">
                    <button type="submit" class="btn-logout"><?= icon('logout', 16) ?> <span>Keluar</span></button>
                </form>
            </div>
        </header>

        <main class="info-section">

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <span class="alert-icon"><?= icon($messageType === 'success' ? 'check' : 'alert', 18) ?></span>
                    <div><?= e($message) ?></div>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalPeserta ?></div>
                    <div class="stat-label">Peserta Terdaftar</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalInfo ?></div>
                    <div class="stat-label">Item Informasi</div>
                </div>
            </div>

            <!-- Daftar Peserta -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div class="admin-section-title">Daftar Peserta</div>
                </div>
                <div class="overflow-x">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>No. HP</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesertaList)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:24px;">Belum ada peserta terdaftar.</td></tr>
                            <?php else: ?>
                                <?php foreach ($pesertaList as $i => $p): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong><?= e($p['nama_lengkap']) ?></strong></td>
                                        <td><?= e($p['departemen']) ?></td>
                                        <td><?= e($p['no_hp']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kelola Informasi -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div class="admin-section-title">Kelola Informasi</div>
                </div>
                <div class="overflow-x">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Urutan</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($infoList as $info): ?>
                                <tr>
                                    <td><?= (int) $info['urutan'] ?></td>
                                    <td><strong><?= e($info['judul']) ?></strong></td>
                                    <td><?= e($info['kategori']) ?></td>
                                    <td>
                                        <span class="badge <?= $info['aktif'] ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= $info['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="action" value="toggle_info">
                                                <input type="hidden" name="id" value="<?= (int) $info['id'] ?>">
                                                <button type="submit" class="btn-sm">
                                                    <?= $info['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                <input type="hidden" name="action" value="delete_info">
                                                <input type="hidden" name="id" value="<?= (int) $info['id'] ?>">
                                                <button type="submit" class="btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tambah Informasi -->
            <div class="admin-section">
                <div class="admin-section-header">
                    <div class="admin-section-title">Tambah Informasi Baru</div>
                </div>
                <div class="admin-section-body">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add_info">
                        
                        <div class="form-group">
                            <label class="form-label" for="judul">Judul</label>
                            <input type="text" id="judul" name="judul" class="form-input" required maxlength="200">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="kategori">Kategori</label>
                                <select id="kategori" name="kategori" class="form-select">
                                    <option value="jadwal">Jadwal</option>
                                    <option value="rute">Rute</option>
                                    <option value="persiapan">Persiapan</option>
                                    <option value="peraturan">Peraturan</option>
                                    <option value="kontak">Kontak</option>
                                    <option value="umum" selected>Umum</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="ikon">Ikon</label>
                                <select id="ikon" name="ikon" class="form-select">
                                    <option value="info">Info</option>
                                    <option value="clock">Jam</option>
                                    <option value="calendar">Kalender</option>
                                    <option value="map">Peta</option>
                                    <option value="bag">Tas</option>
                                    <option value="shield">Perisai</option>
                                    <option value="phone">Telepon</option>
                                    <option value="user">Pengguna</option>
                                    <option value="compass">Kompas</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="urutan">Urutan</label>
                            <input type="number" id="urutan" name="urutan" class="form-input" value="0" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="konten">Konten (HTML)</label>
                            <textarea id="konten" name="konten" required placeholder="<p>Tulis konten di sini...</p>"></textarea>
                            <div class="form-hint">Mendukung tag HTML: &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;table&gt;, &lt;a&gt;, &lt;div&gt;</div>
                        </div>

                        <button type="submit" class="btn btn-primary"><span class="btn-text">Tambah Informasi</span></button>
                    </form>
                </div>
            </div>

        </main>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Admin Panel</p>
        </footer>
    </div>
</body>
</html>
