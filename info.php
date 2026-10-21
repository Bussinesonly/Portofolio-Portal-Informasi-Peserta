<?php
/**
 * Halaman Informasi Perjalanan
 * Portal Informasi Peserta
 * 
 * Halaman utama yang menampilkan seluruh informasi
 * perjalanan bagi peserta yang telah registrasi.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

initSession();

// Guard: harus sudah teregistrasi
if (!isAuthenticated()) {
    redirect('register.php');
}

// Ambil data peserta dari sesi
$pesertaNama = $_SESSION['peserta_nama'] ?? 'Peserta';
$pesertaDept = $_SESSION['peserta_dept'] ?? '';
$pesertaId   = $_SESSION['peserta_id']   ?? 0;

// Update last_access
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE peserta SET last_access = NOW() WHERE id = :id");
    $stmt->execute(['id' => $pesertaId]);
} catch (PDOException $e) {
    error_log('Update last_access error: ' . $e->getMessage());
}

// Ambil informasi yang aktif, diurutkan
try {
    $infoStmt = $pdo->prepare("
        SELECT judul, ikon, konten, kategori 
        FROM informasi 
        WHERE aktif = 1 
        ORDER BY urutan ASC, id ASC
    ");
    $infoStmt->execute();
    $infoList = $infoStmt->fetchAll();
} catch (PDOException $e) {
    $infoList = [];
    error_log('Fetch info error: ' . $e->getMessage());
}

// Kelompokkan berdasarkan kategori
$grouped = [];
foreach ($infoList as $info) {
    $grouped[$info['kategori']][] = $info;
}

// Mapping label kategori
$kategoriLabels = [
    'jadwal'     => 'Jadwal & Acara',
    'rute'       => 'Rute Perjalanan',
    'persiapan'  => 'Persiapan',
    'peraturan'  => 'Peraturan',
    'kontak'     => 'Kontak',
    'umum'       => 'Informasi Umum',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Informasi perjalanan lengkap bagi peserta terdaftar.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f2140">
    <title>Informasi Perjalanan — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= APP_VERSION ?>">
</head>
<body>
    <div class="page-wrapper">

        <!-- Header -->
        <header class="header">
            <div class="header-inner">
                <div class="header-brand">
                    <div class="header-logo">
                        <?= icon('compass', 22) ?>
                    </div>
                    <div>
                        <div class="header-title"><?= e(APP_NAME) ?></div>
                        <div class="header-subtitle">Informasi Kegiatan</div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout" title="Keluar">
                    <?= icon('logout', 16) ?>
                    <span>Keluar</span>
                </a>
            </div>
        </header>

        <!-- Welcome Banner -->
        <section class="welcome-banner">
            <div class="welcome-content">
                <div class="welcome-greeting">Halo,</div>
                <div class="welcome-name"><?= e($pesertaNama) ?></div>
                <?php if ($pesertaDept): ?>
                    <div class="welcome-dept">
                        <span class="welcome-dept-badge"><?= e($pesertaDept) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Info Cards -->
        <main class="info-section">
            <?php if (empty($infoList)): ?>
                <div class="alert alert-warning">
                    <span class="alert-icon"><?= icon('info', 18) ?></span>
                    <div>Informasi perjalanan belum tersedia. Silakan periksa kembali nanti.</div>
                </div>
            <?php else: ?>

                <?php 
                $cardIndex = 0;
                foreach ($grouped as $kategori => $items): 
                    $label = $kategoriLabels[$kategori] ?? ucfirst($kategori);
                ?>
                    <div class="section-title"><?= e($label) ?></div>

                    <?php foreach ($items as $item): ?>
                        <div class="info-card fade-in-up" id="card-<?= $cardIndex ?>">
                            <div class="info-card-header" 
                                 role="button" 
                                 tabindex="0"
                                 aria-expanded="<?= $cardIndex === 0 ? 'true' : 'false' ?>"
                                 aria-controls="card-body-<?= $cardIndex ?>">
                                <div class="info-card-icon">
                                    <?= icon($item['ikon'] ?? 'info', 20) ?>
                                </div>
                                <div class="info-card-title"><?= e($item['judul']) ?></div>
                                <div class="info-card-chevron">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="info-card-body" id="card-body-<?= $cardIndex ?>">
                                <div class="info-card-content">
                                    <?= $item['konten'] ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        $cardIndex++;
                    endforeach; ?>

                <?php endforeach; ?>

            <?php endif; ?>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
            <p><a href="privacy.php">Kebijakan Privasi</a></p>
        </footer>
    </div>

    <script src="script.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
