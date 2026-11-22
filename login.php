<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid atau kedaluwarsa. Silakan muat ulang halaman dan coba lagi.';
    } elseif (empty($identifier) || empty($password)) {
        $error = 'Silakan isi No. WhatsApp / Email dan Password.';
    } else {
        $db = get_db();
        
        // Cari berdasarkan email atau no_telp atau kode_member
        $stmt = $db->prepare("SELECT * FROM va_member WHERE email = ? OR no_telp = ? OR kode_member = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $member = $stmt->fetch();

        if (!$member) {
            $error = 'Akun tidak ditemukan. Periksa kembali No. WhatsApp atau Email Anda.';
        } elseif ($member['status'] !== 'aktif') {
            $error = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin Visi Yosindo.';
        } else {
            // Verifikasi Password
            $password_valid = false;
            
            if (password_verify($password, $member['password'])) {
                $password_valid = true;
            } elseif ($member['password'] === md5($password)) {
                $password_valid = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $up = $db->prepare("UPDATE va_member SET password = ? WHERE id_member = ?");
                $up->execute([$new_hash, $member['id_member']]);
            } elseif (empty($member['password'])) {
                $error = 'Akun Anda belum memiliki password. Silakan hubungi admin Visi Yosindo atau daftar ulang.';
            }

            if ($password_valid) {
                $_SESSION['member_id'] = $member['id_member'];
                $_SESSION['member_name'] = $member['nama_member'];
                $_SESSION['member_code'] = $member['kode_member'];

                set_flash('success', 'Selamat datang kembali, ' . $member['nama_member'] . '!');
                header('Location: dashboard.php');
                exit;
            } else {
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
