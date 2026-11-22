<?php
require_once __DIR__ . '/config.php';
require_login();

$member = current_member();
$db = get_db();

// Ambil statistik komisi & deals member
$stats_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_prospek,
        SUM(CASE WHEN status_deal = 'Deal' THEN 1 ELSE 0 END) as total_deal,
        SUM(CASE WHEN status_deal = 'Pending' THEN 1 ELSE 0 END) as total_pending,
        SUM(CASE WHEN status_deal = 'Batal' THEN 1 ELSE 0 END) as total_batal,
        COALESCE(SUM(CASE WHEN status_deal = 'Deal' THEN nominal_komisi ELSE 0 END), 0) as komisi_deal,
        COALESCE(SUM(CASE WHEN status_deal = 'Deal' AND status_bayar = 'Sudah Ditransfer' THEN nominal_komisi ELSE 0 END), 0) as komisi_tertransfer,
        COALESCE(SUM(CASE WHEN status_deal = 'Deal' AND (status_bayar = 'Belum Ditransfer' OR status_bayar IS NULL) THEN nominal_komisi ELSE 0 END), 0) as komisi_menunggu_transfer,
        COALESCE(SUM(CASE WHEN status_deal = 'Pending' THEN nominal_komisi ELSE 0 END), 0) as komisi_pending,
        COALESCE(SUM(estimasi_nilai), 0) as total_estimasi_nilai
    FROM va_member_deal 
    WHERE id_member = ?
");
$stats_stmt->execute([$member['id_member']]);
$stats = $stats_stmt->fetch();

// Ambil 5 prospek terbaru
$recent_stmt = $db->prepare("
    SELECT * FROM va_member_deal 
    WHERE id_member = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_stmt->execute([$member['id_member']]);
$recent_deals = $recent_stmt->fetchAll();

$has_bank = !empty($member['nama_bank']) && !empty($member['no_rekening']);

$page_title = 'Dashboard Member';
require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-navy-950 via-vym-900 to-vym-800 rounded-3xl p-6 sm:p-8 text-white shadow-md border border-vym-800/60 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-80 h-80 bg-vym-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white p-1.5 shadow-md flex items-center justify-center flex-shrink-0">
                    <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="bg-vym-500/20 text-vym-200 text-xs font-bold px-2.5 py-0.5 rounded-full border border-vym-400/30 font-mono">
                            <i class="fa-solid fa-id-card-clip mr-1"></i> <?= htmlspecialchars($member['kode_member']) ?>
                        </span>
                        <span class="bg-emerald-500/20 text-emerald-300 text-xs font-semibold px-2.5 py-0.5 rounded-full border border-emerald-500/30">
                            <i class="fa-solid fa-circle-check mr-1"></i> Member Aktif
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Halo, <?= htmlspecialchars($member['nama_member']) ?>!
                    </h1>
                    <p class="text-vym-200 text-xs sm:text-sm mt-0.5">
                        <?= htmlspecialchars($member['institusi_asal'] ?: 'Kemitraan Visi Academy') ?> • Selamat datang di portal referral komisi alat kesehatan PT Visi Yosindo Medikal.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="katalog.php" class="bg-slate-900/80 hover:bg-slate-800 text-white font-semibold px-4 py-3 rounded-xl border border-vym-500/40 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-kit-medical text-vym-300"></i> Katalog Alkes
                </a>
                <a href="ajukan-deal.php" class="bg-vym-600 hover:bg-vym-500 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-vym-600/30 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-circle-plus"></i> Ajukan Alkes Baru
                </a>
                <a href="profil.php" class="bg-slate-900/80 hover:bg-slate-800 text-slate-200 font-semibold px-4 py-3 rounded-xl border border-slate-700 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-building-columns"></i> Rekening
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Rekening Belum Lengkap -->
    <?php if (!$has_bank): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 sm:p-5 rounded-2xl shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 text-lg">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Rekening Pencairan Komisi Belum Diisi</h3>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Lengkapi nama bank dan nomor rekening Anda agar komisi referral dapat segera kami transfer begitu prospek berstatus <strong>Deal</strong>.
                    </p>
                </div>
            </div>
            <a href="profil.php" class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition whitespace-nowrap self-start sm:self-center">
                <i class="fa-solid fa-pen-to-square"></i> Lengkapi Sekarang
            </a>
        </div>
    <?php endif; ?>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Card 1: Komisi Deal -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Komisi Deal</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">
                <?= format_rupiah($stats['komisi_deal']) ?>
            </div>
            <div class="flex flex-col gap-0.5 text-[11px] text-slate-600 mt-2 pt-2 border-t border-slate-100">
                <div class="flex items-center justify-between text-emerald-700 font-semibold">
                    <span><i class="fa-solid fa-check-double mr-1"></i> Ditransfer:</span>
                    <span><?= format_rupiah($stats['komisi_tertransfer']) ?></span>
                </div>
                <div class="flex items-center justify-between text-amber-700 font-semibold">
                    <span><i class="fa-solid fa-hourglass-half mr-1"></i> Antrean Transfer:</span>
                    <span><?= format_rupiah($stats['komisi_menunggu_transfer']) ?></span>
                </div>
            </div>
        </div>

        <!-- Card 2: Komisi Pending -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Estimasi Komisi Pending</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">
                <?= format_rupiah($stats['komisi_pending']) ?>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-amber-600 font-semibold">
                <i class="fa-solid fa-spinner animate-spin"></i>
                <span><?= (int)$stats['total_pending'] ?> Sedang Dalam Follow-up</span>
            </div>
        </div>

        <!-- Card 3: Total Pengajuan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Prospek Diajukan</span>
                <div class="w-10 h-10 rounded-xl bg-vym-50 text-vym-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">
                <?= (int)$stats['total_prospek'] ?> <span class="text-sm font-normal text-slate-500">Alkes</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <span class="text-emerald-600 font-medium"><?= (int)$stats['total_deal'] ?> Deal</span> •
                <span class="text-amber-600 font-medium"><?= (int)$stats['total_pending'] ?> Pending</span> •
                <span class="text-slate-400 font-medium"><?= (int)$stats['total_batal'] ?> Batal</span>
            </div>
        </div>

        <!-- Card 4: Info Rekening Bank -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rekening Pencairan</span>
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
            <?php if ($has_bank): ?>
                <div class="text-base font-bold text-slate-900 mb-0.5">
                    <?= htmlspecialchars($member['nama_bank']) ?>
                </div>
                <div class="text-sm font-mono text-slate-700 font-semibold">
                    <?= htmlspecialchars($member['no_rekening']) ?>
                </div>
                <div class="text-xs text-slate-500 truncate mt-1">
                    a.n <?= htmlspecialchars($member['atas_nama_rekening'] ?: $member['nama_member']) ?>
                </div>
            <?php else: ?>
                <div class="text-sm font-bold text-amber-600 mb-1">Belum Diatur</div>
                <p class="text-xs text-slate-500 mb-2">Tambahkan nomor rekening untuk transfer komisi.</p>
                <a href="profil.php" class="text-xs text-vym-600 font-bold hover:underline">
                    + Input Rekening Sekarang
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Deals Section -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Prospek & Kebutuhan Alkes Terbaru</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar pengajuan terakhir yang Anda daftarkan ke Visi Yosindo Medikal</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="tracking-deal.php" class="text-xs font-bold text-vym-600 hover:text-vym-700 hover:underline flex items-center gap-1">
                    Lihat Semua Prospek <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-5 py-3.5">Nama Produk / Alkes</th>
                        <th class="px-5 py-3.5">Estimasi Nilai</th>
                        <th class="px-5 py-3.5">Persen Komisi</th>
                        <th class="px-5 py-3.5">Estimasi Komisi</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($recent_deals)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3 text-slate-400">
                                    <i class="fa-solid fa-inbox text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-700">Belum ada pengajuan kebutuhan alkes.</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    Apakah Rumah Sakit, Klinik, atau rekan sejawat Anda membutuhkan alat kesehatan baru?
                                </p>
                                <a href="ajukan-deal.php" class="inline-flex items-center gap-2 mt-4 bg-vym-600 hover:bg-vym-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
                                    <i class="fa-solid fa-circle-plus"></i> Ajukan Prospek Sekarang
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_deals as $deal): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500">
                                    <?= format_tanggal_id($deal['created_at']) ?>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($deal['nama_produk']) ?></div>
                                    <?php if (!empty($deal['catatan'])): ?>
                                        <div class="text-xs text-slate-500 line-clamp-1 mt-0.5">
                                            <?= htmlspecialchars($deal['catatan']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-700">
                                    <?= format_rupiah($deal['estimasi_nilai']) ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-600 font-semibold">
                                    <?= number_format((float)$deal['persen_komisi'], 1) ?>%
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-bold text-vym-600">
                                    <?= format_rupiah($deal['nominal_komisi']) ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($deal['status_deal'] === 'Deal'): ?>
                                        <?php if (($deal['status_bayar'] ?? '') === 'Sudah Ditransfer'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                <i class="fa-solid fa-check-double text-[10px]"></i> Sudah Ditransfer
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> Deal (Proses Transfer)
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($deal['status_deal'] === 'Pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                            <i class="fa-solid fa-clock text-[10px]"></i> Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                            <i class="fa-solid fa-circle-xmark text-[10px]"></i> Batal
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
