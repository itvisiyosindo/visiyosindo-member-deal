<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$warning = (($_GET['msg'] ?? '') === 'inactive')
    ? 'Sesi berakhir karena akun Anda dinonaktifkan. Silakan hubungi tim PT Visi Yosindo Medikal.'
    : '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $rl = rate_limit_check('login', 5, 600);

    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid atau kedaluwarsa. Silakan muat ulang halaman dan coba lagi.';
    } elseif (!$rl['allowed']) {
        $menit = (int)ceil($rl['retry_after'] / 60);
        $error = "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$menit} menit.";
    } elseif (empty($identifier) || empty($password)) {
        $error = 'Silakan isi No. WhatsApp / Email dan Password.';
    } else {
        $db = get_db();
        $norm_email = normalize_email($identifier);
        $norm_phone = normalize_phone($identifier);

        // Cari berdasarkan email (case-insensitive), kode_member, atau no_telp (termasuk format lama/62)
        $sql = "SELECT * FROM va_member WHERE LOWER(email) = ? OR kode_member = ? OR no_telp = ?";
        $params = [$norm_email, $identifier, $identifier];
        if ($norm_phone !== '') {
            $sql .= " OR REPLACE(REPLACE(REPLACE(no_telp, '-', ''), ' ', ''), '+', '') = ?";
            $params[] = $norm_phone;
            if (strpos($norm_phone, '0') === 0) {
                $sql .= " OR REPLACE(REPLACE(REPLACE(no_telp, '-', ''), ' ', ''), '+', '') = ?";
                $params[] = '62' . substr($norm_phone, 1);
            }
        }
        $sql .= " LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $member = $stmt->fetch();

        if (!$member) {
            rate_limit_hit('login', 600);
            $error = 'Akun tidak ditemukan. Periksa kembali No. WhatsApp atau Email Anda.';
        } elseif (($member['status'] ?? '') !== 'aktif') {
            rate_limit_hit('login', 600);
            $error = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin Visi Yosindo.';
        } else {
            // Cek password kosong lebih dulu agar PHP 8.3 tidak warning saat hash NULL
            $stored_hash = (string)($member['password'] ?? '');
            $password_valid = false;

            if ($stored_hash === '') {
                $error = 'Akun Anda belum memiliki password. Silakan hubungi admin Visi Yosindo untuk aktivasi.';
            } elseif (password_verify($password, $stored_hash)) {
                $password_valid = true;
            } elseif (hash_equals($stored_hash, md5($password))) {
                $password_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $up = $db->prepare("UPDATE va_member SET password = ? WHERE id_member = ?");
                $up->execute([$new_hash, $member['id_member']]);
            }

            if ($password_valid) {
                rate_limit_reset('login');
                session_regenerate_id(true);
                rotate_csrf();
                $_SESSION['member_id'] = (int)$member['id_member'];

                set_flash('success', 'Selamat datang kembali, ' . $member['nama_member'] . '!');
                header('Location: dashboard.php');
                exit;
            } else {
                rate_limit_hit('login', 600);
                if (empty($error)) {
                    $error = 'Password yang Anda masukkan salah.';
                }
            }
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-md mx-auto my-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-navy-950 via-vym-900 to-vym-800 p-6 text-white text-center">
            <div class="w-16 h-16 bg-white rounded-2xl p-1.5 flex items-center justify-center mx-auto mb-3 shadow-md">
                <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
            </div>
            <h2 class="text-xl font-bold">Portal Member Visi Academy</h2>
            <p class="text-xs text-vym-200 mt-1">Silakan masuk untuk mengakses dashboard referral komisi</p>
        </div>

        <!-- Form Body -->
        <div class="p-6 sm:p-8">
            <?php if (!empty($warning)): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                    <span><?= htmlspecialchars($warning) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        No. WhatsApp / Email / Kode Member
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-address-card"></i>
                        </span>
                        <input type="text" name="identifier" value="<?= htmlspecialchars($identifier) ?>" required
                            placeholder="Contoh: 08123456789 atau nama@email.com"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Password
                        </label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="loginPassword" name="password" required
                            placeholder="Masukkan password Anda"
                            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        <button type="button" onclick="togglePasswordVisibility('loginPassword', this)" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-vym-600 hover:bg-vym-700 text-white font-bold py-3 rounded-xl shadow-md shadow-vym-600/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
                Belum punya akun?
                <a href="register.php" class="font-bold text-vym-600 hover:text-vym-700 hover:underline ml-1">
                    Daftar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
