<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$form = [
    'nama_member' => '',
    'no_telp' => '',
    'email' => '',
    'institusi_asal' => '',
    'nama_bank' => '',
    'no_rekening' => '',
    'atas_nama_rekening' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_member'] = trim($_POST['nama_member'] ?? '');
    $form['no_telp'] = normalize_phone($_POST['no_telp'] ?? '');
    $form['email'] = normalize_email($_POST['email'] ?? '');
    $form['institusi_asal'] = trim($_POST['institusi_asal'] ?? '');
    $form['nama_bank'] = trim($_POST['nama_bank'] ?? '');
    $form['no_rekening'] = trim($_POST['no_rekening'] ?? '');
    $form['atas_nama_rekening'] = trim($_POST['atas_nama_rekening'] ?? '');

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $rl = rate_limit_check('register', 5, 600);

    // Validasi Dasar, Panjang Kolom DB, Rate Limit & CSRF
    if (!verify_csrf()) {
        $error = 'Sesi formulir tidak valid atau kedaluwarsa. Silakan muat ulang halaman dan coba lagi.';
    } elseif (!$rl['allowed']) {
        $menit = (int)ceil($rl['retry_after'] / 60);
        $error = "Terlalu banyak percobaan pendaftaran. Silakan coba lagi dalam {$menit} menit.";
    } elseif (empty($form['nama_member']) || empty($form['no_telp']) || empty($form['email']) || empty($password)) {
        $error = 'Nama Lengkap, No. WhatsApp, Email, dan Password wajib diisi.';
    } elseif (strlen($form['no_telp']) < 9 || strlen($form['no_telp']) > 15) {
        $error = 'Nomor WhatsApp harus terdiri dari 9 hingga 15 digit angka.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 100) {
        $error = 'Format email tidak valid atau terlalu panjang.';
    } elseif (mb_strlen($form['nama_member']) > 255 || mb_strlen($form['institusi_asal']) > 255) {
        $error = 'Nama lengkap atau institusi maksimal 255 karakter.';
    } elseif (mb_strlen($form['nama_bank']) > 50 || mb_strlen($form['no_rekening']) > 50 || mb_strlen($form['atas_nama_rekening']) > 100) {
        $error = 'Data rekening melebihi batas panjang yang diizinkan.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok dengan password.';
    } else {
        $db = get_db();

        // Cek duplikasi No. Telp (termasuk format 08/628/strip) atau Email (case-insensitive)
        $phone_alt = (strpos($form['no_telp'], '0') === 0) ? ('62' . substr($form['no_telp'], 1)) : $form['no_telp'];
        $chk = $db->prepare("
            SELECT id_member FROM va_member
            WHERE LOWER(email) = ?
               OR REPLACE(REPLACE(REPLACE(no_telp, '-', ''), ' ', ''), '+', '') IN (?, ?)
            LIMIT 1
        ");
        $chk->execute([$form['email'], $form['no_telp'], $phone_alt]);
        if ($chk->fetch()) {
            $error = 'Nomor WhatsApp atau Email sudah terdaftar sebelumnya. Silakan langsung login.';
        } else {
            $hash_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $db->prepare("
                INSERT INTO va_member
                (kode_member, nama_member, no_telp, email, password, institusi_asal, nama_bank, no_rekening, atas_nama_rekening, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())
            ");

            // Retry hingga 3x jika terjadi bentrok UNIQUE kode_member pada pendaftaran bersamaan
            $new_id = 0;
            $kode_member = '';
            $saved = false;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $kode_member = generate_kode_member($db);
                try {
                    $insert->execute([
                        $kode_member,
                        $form['nama_member'],
                        $form['no_telp'],
                        $form['email'],
                        $hash_password,
                        $form['institusi_asal'],
                        $form['nama_bank'],
                        $form['no_rekening'],
                        $form['atas_nama_rekening']
                    ]);
                    $new_id = (int)$db->lastInsertId();
                    $saved = true;
                    break;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000' && $attempt < 2) {
                        continue;
                    }
                    error_log('[Portal Member Register] DB insert failed: ' . $e->getMessage());
                    $error = 'Terjadi kendala saat menyimpan pendaftaran. Silakan coba beberapa saat lagi.';
                    break;
                }
            }

            if ($saved && $new_id > 0) {
                rate_limit_hit('register', 600);
                session_regenerate_id(true);
                rotate_csrf();
                $_SESSION['member_id'] = $new_id;

                // Kirim notifikasi WA otomatis ke Grup Marketing PT Visi Yosindo Medikal
                notify_marketing_new_member([
                    'kode_member'    => $kode_member,
                    'nama_member'    => $form['nama_member'],
                    'no_telp'        => $form['no_telp'],
                    'email'          => $form['email'],
                    'institusi_asal' => $form['institusi_asal']
                ]);

                set_flash('success', "Pendaftaran berhasil! Selamat bergabung di Portal Member Visi Academy. Kode Member Anda: $kode_member");
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}

$page_title = 'Daftar Akun';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-2xl mx-auto my-6 sm:my-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-navy-950 via-vym-900 to-vym-800 p-6 sm:p-8 text-white text-center">
            <div class="w-16 h-16 bg-white rounded-2xl p-1.5 flex items-center justify-center mx-auto mb-3 shadow-md">
                <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
            </div>
            <span class="inline-block bg-vym-500/20 text-vym-200 text-xs font-semibold px-3 py-1 rounded-full border border-vym-400/30 mb-2">
                Program Referral PT Visi Yosindo Medikal
            </span>
            <h2 class="text-2xl font-bold">Daftar Akun Visi Academy</h2>
            <p class="text-xs text-vym-200 mt-1 max-w-md mx-auto">
                Khusus Dokter, Radiografer, dan Alumni Pelatihan untuk kemitraan pengadaan alat kesehatan.
            </p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm" class="space-y-6">
                <?= csrf_field() ?>
                <!-- Data Pribadi & Kontak -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                        <i class="fa-solid fa-user-doctor text-vym-600"></i> Informasi Pribadi & Instansi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Lengkap & Gelar <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nama_member" value="<?= htmlspecialchars($form['nama_member']) ?>" required
                                placeholder="Contoh: dr. Budi Santoso, Sp.Rad / Ahmad Fauzi, S.Tr.Kes"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                No. WhatsApp Aktif <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="no_telp" value="<?= htmlspecialchars($form['no_telp']) ?>" required
                                placeholder="Contoh: 08123456789"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                            <span class="text-[11px] text-slate-500 mt-1 block">Digunakan untuk login & konfirmasi referral.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Aktif <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required
                                placeholder="Contoh: dokter@email.com"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Rumah Sakit / Klinik / Instansi Asal
                            </label>
                            <input type="text" name="institusi_asal" value="<?= htmlspecialchars($form['institusi_asal']) ?>"
                                placeholder="Contoh: RSUD Pasar Minggu / RS Siloam / Klinik Utama Medika"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>
                    </div>
                </div>

                <!-- Keamanan Password -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                        <i class="fa-solid fa-lock text-vym-600"></i> Keamanan Akun
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" name="password" required minlength="6"
                                placeholder="Minimal 6 karakter"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Konfirmasi Password <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" name="password_confirm" required minlength="6"
                                placeholder="Ulangi password"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>
                    </div>
                </div>

                <!-- Rekening Bank (Opsional) -->
                <div>
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-building-columns text-vym-600"></i> Rekening Pencairan Komisi
                        </h3>
                        <span class="text-[11px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Bisa diisi nanti</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Bank
                            </label>
                            <input type="text" name="nama_bank" list="bankList" value="<?= htmlspecialchars($form['nama_bank']) ?>"
                                placeholder="Pilih / Ketik Bank"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                            <datalist id="bankList">
                                <option value="BCA">
                                <option value="Bank Mandiri">
                                <option value="BRI">
                                <option value="BNI">
                                <option value="BSI (Bank Syariah Indonesia)">
                                <option value="CIMB Niaga">
                                <option value="Bank Permata">
                                <option value="Bank Jatim">
                                <option value="Bank Jateng">
                                <option value="Bank BJB">
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nomor Rekening
                            </label>
                            <input type="text" name="no_rekening" value="<?= htmlspecialchars($form['no_rekening']) ?>"
                                placeholder="Contoh: 1234567890"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Atas Nama Rekening
                            </label>
                            <input type="text" name="atas_nama_rekening" value="<?= htmlspecialchars($form['atas_nama_rekening']) ?>"
                                placeholder="Sesuai buku tabungan"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="registerSubmitBtn" class="w-full bg-vym-600 hover:bg-vym-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-xl shadow-lg shadow-vym-600/25 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-check"></i> Daftar Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
                Sudah punya akun?
                <a href="login.php" class="font-bold text-vym-600 hover:text-vym-700 hover:underline ml-1">
                    Login
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const regForm = document.getElementById('registerForm');
const regBtn = document.getElementById('registerSubmitBtn');
if (regForm && regBtn) {
    regForm.addEventListener('submit', function () {
        regBtn.disabled = true;
        regBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses Pendaftaran...';
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
