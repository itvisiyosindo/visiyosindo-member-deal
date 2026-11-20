<?php
require_once __DIR__ . '/config.php';
require_login();

$member = current_member();
$db = get_db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $nama_member = trim($_POST['nama_member'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $institusi_asal = trim($_POST['institusi_asal'] ?? '');
        $nama_bank = trim($_POST['nama_bank'] ?? '');
        $no_rekening = trim($_POST['no_rekening'] ?? '');
        $atas_nama_rekening = trim($_POST['atas_nama_rekening'] ?? '');

        if (empty($nama_member) || empty($no_telp) || empty($email)) {
            $error = 'Nama Lengkap, No. WhatsApp, dan Email tidak boleh kosong.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek duplikasi no_telp atau email milik member lain
            $chk = $db->prepare("SELECT id_member FROM va_member WHERE (no_telp = ? OR email = ?) AND id_member != ? LIMIT 1");
            $chk->execute([$no_telp, $email, $member['id_member']]);
            if ($chk->fetch()) {
                $error = 'Nomor WhatsApp atau Email sudah digunakan oleh member lain.';
            } else {
                $stmt = $db->prepare("
                    UPDATE va_member 
                    SET nama_member = ?, no_telp = ?, email = ?, institusi_asal = ?, nama_bank = ?, no_rekening = ?, atas_nama_rekening = ?
                    WHERE id_member = ?
                ");
                $stmt->execute([
                    $nama_member,
                    $no_telp,
                    $email,
                    $institusi_asal,
                    $nama_bank,
                    $no_rekening,
                    $atas_nama_rekening,
                    $member['id_member']
                ]);

                set_flash('success', 'Profil dan data rekening pencairan komisi berhasil diperbarui!');
                header('Location: profil.php');
                exit;
            }
        }
    } elseif ($action === 'change_password') {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($password_lama) || empty($password_baru)) {
            $error = 'Password saat ini dan password baru harus diisi.';
        } elseif (!password_verify($password_lama, $member['password']) && $member['password'] !== md5($password_lama)) {
            $error = 'Password saat ini salah.';
        } elseif (strlen($password_baru) < 6) {
            $error = 'Password baru minimal harus 6 karakter.';
        } elseif ($password_baru !== $password_confirm) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $new_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE va_member SET password = ? WHERE id_member = ?");
            $stmt->execute([$new_hash, $member['id_member']]);

            set_flash('success', 'Password Anda berhasil diperbarui!');
            header('Location: profil.php');
            exit;
        }
    }
}

// Refresh data terbaru
$member = current_member();

$page_title = 'Rekening & Profil Member';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-4xl mx-auto my-6 space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-navy-900 to-slate-800 rounded-3xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-teal-500/20 border border-teal-500/30 flex items-center justify-center text-teal-400 text-2xl font-black">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="bg-teal-500/20 text-teal-300 text-xs font-mono font-bold px-2 py-0.5 rounded border border-teal-500/30">
                        <?= htmlspecialchars($member['kode_member']) ?>
                    </span>
                    <span class="text-xs text-slate-300">Bergabung: <?= date('d M Y', strtotime($member['created_at'])) ?></span>
                </div>
                <h1 class="text-2xl font-bold mt-1"><?= htmlspecialchars($member['nama_member']) ?></h1>
                <p class="text-xs text-slate-300"><?= htmlspecialchars($member['institusi_asal'] ?: 'Member Visi Academy') ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation flex-shrink-0 text-base"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Form: Profil & Rekening -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
                <form action="profil.php" method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Bagian Rekening Bank (Utama) -->
                    <div>
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200 mb-4">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-building-columns text-teal-600"></i> Rekening Pencairan Komisi
                            </h2>
                            <span class="text-[11px] font-semibold text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-full border border-teal-200">
                                Sangat Penting
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">
                            Pastikan data nomor rekening dan atas nama rekening sesuai dengan buku tabungan Anda untuk mempermudah transfer pencairan komisi referral alkes.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Bank
                                </label>
                                <input type="text" name="nama_bank" list="bankListProfile" value="<?= htmlspecialchars($member['nama_bank'] ?? '') ?>"
                                    placeholder="Contoh: BCA / Mandiri"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                                <datalist id="bankListProfile">
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
                                <input type="text" name="no_rekening" value="<?= htmlspecialchars($member['no_rekening'] ?? '') ?>"
                                    placeholder="Contoh: 1234567890"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono font-bold focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Atas Nama Rekening
                                </label>
                                <input type="text" name="atas_nama_rekening" value="<?= htmlspecialchars($member['atas_nama_rekening'] ?? '') ?>"
                                    placeholder="Sesuai buku tabungan"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Data Personal -->
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                            <i class="fa-solid fa-id-card text-teal-600"></i> Informasi Pribadi & Faskes
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Lengkap & Gelar <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="nama_member" value="<?= htmlspecialchars($member['nama_member']) ?>" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    No. WhatsApp <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="no_telp" value="<?= htmlspecialchars($member['no_telp']) ?>" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input type="email" name="email" value="<?= htmlspecialchars($member['email']) ?>" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Institusi Asal / Rumah Sakit / Faskes
                                </label>
                                <input type="text" name="institusi_asal" value="<?= htmlspecialchars($member['institusi_asal']) ?>"
                                    placeholder="Contoh: RSUD Pasar Minggu"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-bold py-3 rounded-xl shadow-md shadow-teal-600/20 transition flex items-center justify-center gap-2 text-sm">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil & Rekening
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar: Ganti Password & Info Akun -->
        <div class="space-y-6">
            <!-- Form Ganti Password -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                    <i class="fa-solid fa-key text-teal-600"></i> Ganti Password
                </h3>
                <form action="profil.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Password Saat Ini
                        </label>
                        <input type="password" name="password_lama" required
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Password Baru
                        </label>
                        <input type="password" name="password_baru" required minlength="6"
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Ulangi Password Baru
                        </label>
                        <input type="password" name="password_confirm" required minlength="6"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-lock"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kartu Member Card -->
            <div class="bg-gradient-to-br from-navy-950 to-slate-900 p-5 rounded-2xl border border-slate-800 text-white shadow-md relative overflow-hidden">
                <div class="text-[11px] font-bold text-teal-400 uppercase tracking-widest mb-3">Visi Academy Member Card</div>
                <div class="text-base font-black tracking-wide"><?= htmlspecialchars($member['nama_member']) ?></div>
                <div class="text-xs font-mono text-slate-300 mt-1"><?= htmlspecialchars($member['kode_member']) ?></div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>PT Visi Yosindo Medikal</span>
                    <span class="text-emerald-400 font-bold">Terverifikasi</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
