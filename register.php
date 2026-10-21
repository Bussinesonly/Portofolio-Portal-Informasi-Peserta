<?php
/**
 * Halaman Registrasi Peserta
 * Portal Informasi Peserta
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

initSession();

// Jika sudah teautentikasi, langsung ke info
if (isAuthenticated()) {
    redirect('info.php');
}

$errors  = [];
$success = false;
$old     = ['nama' => '', 'departemen' => '', 'no_hp' => ''];

// Daftar departemen
$departemenList = [
    'Human Resources',
    'Finance & Accounting',
    'Marketing & Sales',
    'Information Technology',
    'Operations',
    'Research & Development',
    'Legal & Compliance',
    'Customer Service',
    'General Affairs',
    'Procurement',
    'Quality Assurance',
    'Public Relations',
    'Lainnya',
];

// ─── Proses Form ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $csrfInput = $_POST['csrf_token'] ?? '';
    if (!validateCsrf($csrfInput)) {
        $errors[] = 'Sesi tidak valid. Silakan muat ulang halaman dan coba lagi.';
    }

    // Ambil dan bersihkan input
    $nama       = trim($_POST['nama'] ?? '');
    $departemen = trim($_POST['departemen'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');
    $consent    = isset($_POST['consent']);

    $old = ['nama' => $nama, 'departemen' => $departemen, 'no_hp' => $no_hp];

    // Validasi nama
    if (empty($nama)) {
        $errors[] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $errors[] = 'Nama minimal 3 karakter.';
    } elseif (mb_strlen($nama) > 150) {
        $errors[] = 'Nama maksimal 150 karakter.';
    } elseif (!preg_match("/^[a-zA-Z\s'.,\-]+$/u", $nama)) {
        $errors[] = 'Nama hanya boleh mengandung huruf dan spasi.';
    }

    // Validasi departemen
    if (empty($departemen)) {
        $errors[] = 'Silakan pilih departemen.';
    } elseif (!in_array($departemen, $departemenList, true)) {
        $errors[] = 'Departemen yang dipilih tidak valid.';
    }

    // Validasi dan normalisasi no HP
    // Dari form, user memasukkan tanpa awalan 0 (prefix +62 ditampilkan terpisah)
    $no_hp = preg_replace('/[\s\-\.\(\)]/', '', $no_hp);
    if (!str_starts_with($no_hp, '0') && !str_starts_with($no_hp, '+')) {
        $no_hp = '0' . $no_hp;
    }
    $no_hp = normalizePhone($no_hp);
    $old['no_hp'] = $no_hp;

    if (empty($no_hp)) {
        $errors[] = 'Nomor HP wajib diisi.';
    } elseif (!isValidPhone($no_hp)) {
        $errors[] = 'Format nomor HP tidak valid. Gunakan format: 08xxxxxxxxx';
    }

    // Validasi consent
    if (!$consent) {
        $errors[] = 'Anda harus menyetujui pemrosesan data pribadi.';
    }

    // Simpan ke database jika valid
    if (empty($errors)) {
        try {
            $pdo = getDB();

            // Cek duplikasi berdasarkan kombinasi nama + no_hp
            $checkStmt = $pdo->prepare("
                SELECT id, token FROM peserta 
                WHERE no_hp = :no_hp AND nama_lengkap = :nama
                LIMIT 1
            ");
            $checkStmt->execute(['no_hp' => $no_hp, 'nama' => $nama]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Peserta sudah terdaftar, gunakan token yang ada
                $token = $existing['token'];
                $pesertaId = $existing['id'];

                // Update last_access
                $updateStmt = $pdo->prepare("
                    UPDATE peserta SET last_access = NOW() WHERE id = :id
                ");
                $updateStmt->execute(['id' => $pesertaId]);
            } else {
                // Registrasi baru
                $token = generateToken();
                $insertStmt = $pdo->prepare("
                    INSERT INTO peserta (nama_lengkap, departemen, no_hp, token, created_at, last_access) 
                    VALUES (:nama, :dept, :no_hp, :token, NOW(), NOW())
                ");
                $insertStmt->execute([
                    'nama'  => $nama,
                    'dept'  => $departemen,
                    'no_hp' => $no_hp,
                    'token' => $token,
                ]);
                $pesertaId = (int) $pdo->lastInsertId();
            }

            // Set session
            $_SESSION['peserta_id']    = $pesertaId;
            $_SESSION['peserta_token'] = $token;
            $_SESSION['peserta_nama']  = $nama;
            $_SESSION['peserta_dept']  = $departemen;

            // Regenerate session ID setelah login
            session_regenerate_id(true);

            // Reset CSRF
            unset($_SESSION['csrf_token']);

            redirect('info.php');

        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
            // Log error (pada produksi, gunakan error_log)
            error_log('Registration error: ' . $e->getMessage());
        }
    }

    // Regenerate CSRF token setelah submission
    unset($_SESSION['csrf_token']);
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Portal informasi perjalanan bagi peserta yang telah melakukan registrasi.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f2140">
    <title>Registrasi — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="style.css?v=<?= APP_VERSION ?>">
</head>
<body>
    <div class="page-wrapper">

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-logo">
                <?= icon('compass', 32) ?>
            </div>
            <h1>Selamat Datang, Peserta!</h1>
            <p>Daftarkan diri Anda untuk mengakses informasi lengkap perjalanan kegiatan.</p>
        </section>

        <!-- Form Section -->
        <section class="form-section">
            <div class="form-card">
                <h2 class="form-title">Registrasi Peserta</h2>
                <p class="form-desc">Lengkapi data berikut untuk melanjutkan ke portal informasi.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" role="alert">
                        <span class="alert-icon"><?= icon('alert', 18) ?></span>
                        <div>
                            <?php foreach ($errors as $err): ?>
                                <div><?= e($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form id="registerForm" method="POST" action="register.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

                    <!-- Nama Lengkap -->
                    <div class="form-group">
                        <label class="form-label" for="nama">
                            Nama Lengkap <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="nama" 
                            name="nama" 
                            class="form-input" 
                            placeholder="Masukkan nama lengkap Anda"
                            value="<?= e($old['nama']) ?>"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                        <div class="field-error" id="namaError"></div>
                    </div>

                    <!-- Departemen -->
                    <div class="form-group">
                        <label class="form-label" for="departemen">
                            Departemen <span class="required">*</span>
                        </label>
                        <select id="departemen" name="departemen" class="form-select" required>
                            <option value="">— Pilih Departemen —</option>
                            <?php foreach ($departemenList as $dept): ?>
                                <option value="<?= e($dept) ?>" <?= $old['departemen'] === $dept ? 'selected' : '' ?>>
                                    <?= e($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="field-error" id="departemenError"></div>
                    </div>

                    <!-- Nomor HP -->
                    <div class="form-group">
                        <label class="form-label" for="no_hp">
                            Nomor HP <span class="required">*</span>
                        </label>
                        <div class="phone-input-group">
                            <span class="phone-prefix">+62</span>
                            <input 
                                type="tel" 
                                id="no_hp" 
                                name="no_hp" 
                                class="form-input" 
                                placeholder="8123456789"
                                value="<?= e(ltrim($old['no_hp'], '0')) ?>"
                                maxlength="15"
                                inputmode="tel"
                                autocomplete="tel"
                                required
                            >
                        </div>
                        <div class="form-hint">Format: 8xxxxxxxxxx (tanpa angka 0 di depan)</div>
                        <div class="field-error" id="no_hpError"></div>
                    </div>

                    <!-- Consent -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="consent" name="consent" required>
                            <label class="form-check-label" for="consent">
                                Saya menyetujui pemrosesan data pribadi saya sesuai dengan 
                                <a href="privacy.php" target="_blank">Kebijakan Privasi</a> 
                                untuk keperluan kegiatan ini.
                            </label>
                        </div>
                        <div class="field-error" id="consentError"></div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <span class="btn-text">Daftar dan Lanjutkan</span>
                    </button>
                </form>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Seluruh hak cipta dilindungi.</p>
        </footer>
    </div>

    <script src="script.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
