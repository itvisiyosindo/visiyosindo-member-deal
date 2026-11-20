<?php
require_once __DIR__ . '/config.php';
require_login();

$member = current_member();
$db = get_db();

// Filter Status & Search
$status_filter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['q'] ?? '');

$query = "SELECT * FROM va_member_deal WHERE id_member = ?";
$params = [$member['id_member']];

if (in_array($status_filter, ['Pending', 'Deal', 'Batal'])) {
    $query .= " AND status_deal = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (nama_produk LIKE ? OR catatan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$deals = $stmt->fetchAll();

// Hitung total ringkasan untuk filter tab
$count_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_all,
        SUM(CASE WHEN status_deal = 'Deal' THEN 1 ELSE 0 END) as total_deal,
        SUM(CASE WHEN status_deal = 'Pending' THEN 1 ELSE 0 END) as total_pending,
        SUM(CASE WHEN status_deal = 'Batal' THEN 1 ELSE 0 END) as total_batal
    FROM va_member_deal 
    WHERE id_member = ?
");
$count_stmt->execute([$member['id_member']]);
$counts = $count_stmt->fetch();

$page_title = 'Tracking Status Prospek Alkes';
require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Tracking Status Prospek</h1>
            <p class="text-xs text-slate-500 mt-1">Pantau perkembangan prospek alat kesehatan dan kepastian komisi referral Anda</p>
        </div>
        <div>
            <a href="ajukan-deal.php" class="bg-teal-600 hover:bg-teal-500 text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-circle-plus"></i> Ajukan Alkes Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            <a href="tracking-deal.php?status=all<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $status_filter === 'all' ? 'bg-navy-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Semua (<?= (int)$counts['total_all'] ?>)
            </a>
            <a href="tracking-deal.php?status=Pending<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $status_filter === 'Pending' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' ?>">
                <i class="fa-solid fa-clock text-[10px] mr-1"></i> Pending (<?= (int)$counts['total_pending'] ?>)
            </a>
            <a href="tracking-deal.php?status=Deal<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $status_filter === 'Deal' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                <i class="fa-solid fa-circle-check text-[10px] mr-1"></i> Deal (<?= (int)$counts['total_deal'] ?>)
            </a>
            <a href="tracking-deal.php?status=Batal<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $status_filter === 'Batal' ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                <i class="fa-solid fa-circle-xmark text-[10px] mr-1"></i> Batal (<?= (int)$counts['total_batal'] ?>)
            </a>
        </div>

        <!-- Search Form -->
        <form action="tracking-deal.php" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
            <div class="relative flex-1 md:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari alkes / RS / catatan..."
                    class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition">
            </div>
            <?php if (!empty($search)): ?>
                <a href="tracking-deal.php?status=<?= urlencode($status_filter) ?>" class="text-slate-400 hover:text-slate-600 p-1 text-xs" title="Reset Pencarian">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
            <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                Cari
            </button>
        </form>
    </div>

    <!-- Deals Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">ID / Tanggal</th>
                        <th class="px-5 py-3.5">Produk Alkes & Kebutuhan</th>
                        <th class="px-5 py-3.5">Estimasi Pengadaan</th>
                        <th class="px-5 py-3.5">Komisi Referral</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($deals)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-slate-500">
                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3 text-slate-400 text-2xl">
                                    <i class="fa-solid fa-folder-open"></i>
                                </div>
                                <p class="text-base font-bold text-slate-800">Tidak ada data prospek ditemukan.</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                    <?= !empty($search) || $status_filter !== 'all' ? 'Coba ubah kata kunci pencarian atau reset filter status.' : 'Daftarkan kebutuhan alkes sekarang untuk memulai referral komisi Anda.' ?>
                                </p>
                                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                                    <a href="tracking-deal.php" class="inline-flex items-center gap-1.5 mt-4 text-xs font-bold text-teal-600 hover:underline">
                                        <i class="fa-solid fa-rotate-left"></i> Reset Filter
                                    </a>
                                <?php else: ?>
                                    <a href="ajukan-deal.php" class="inline-flex items-center gap-2 mt-4 bg-teal-600 hover:bg-teal-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
                                        <i class="fa-solid fa-circle-plus"></i> Ajukan Prospek Baru
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deals as $deal): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500">
                                    <span class="font-mono font-bold text-slate-700 block">#DL-<?= str_pad($deal['id_deal'], 4, '0', STR_PAD_LEFT) ?></span>
                                    <span><?= format_tanggal_id($deal['created_at']) ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($deal['nama_produk']) ?></div>
                                    <?php if (!empty($deal['catatan'])): ?>
                                        <div class="text-xs text-slate-500 line-clamp-2 mt-0.5 whitespace-pre-line">
                                            <?= htmlspecialchars($deal['catatan']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-800">
                                    <?= format_rupiah($deal['estimasi_nilai']) ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-black text-teal-700"><?= format_rupiah($deal['nominal_komisi']) ?></div>
                                    <div class="text-[11px] text-slate-400 font-semibold">(<?= number_format((float)$deal['persen_komisi'], 1) ?>%)</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <?php if ($deal['status_deal'] === 'Deal'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i> Deal (Komisi Cair)
                                        </span>
                                    <?php elseif ($deal['status_deal'] === 'Pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="fa-solid fa-clock text-[10px]"></i> Pending Follow-up
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <i class="fa-solid fa-circle-xmark text-[10px]"></i> Batal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button type="button" 
                                        onclick="showDetailModal(<?= htmlspecialchars(json_encode([
                                            'id' => '#DL-' . str_pad($deal['id_deal'], 4, '0', STR_PAD_LEFT),
                                            'tanggal' => format_tanggal_id($deal['created_at']),
                                            'produk' => $deal['nama_produk'],
                                            'estimasi' => format_rupiah($deal['estimasi_nilai']),
                                            'persen' => number_format((float)$deal['persen_komisi'], 1) . '%',
                                            'komisi' => format_rupiah($deal['nominal_komisi']),
                                            'status' => $deal['status_deal'],
                                            'catatan' => $deal['catatan'] ?: 'Tidak ada catatan tambahan.'
                                        ])) ?>)"
                                        class="text-xs font-bold text-teal-600 hover:text-teal-800 bg-teal-50 hover:bg-teal-100 px-3 py-1.5 rounded-lg transition">
                                        <i class="fa-solid fa-eye mr-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Prospek -->
<div id="detailModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-fade-in">
        <div class="bg-gradient-to-r from-navy-900 to-slate-800 p-5 text-white flex items-center justify-between">
            <div>
                <span id="modalId" class="text-xs font-mono font-bold text-teal-300 bg-teal-900/60 px-2 py-0.5 rounded">#DL-0000</span>
                <h3 class="text-lg font-bold mt-1">Detail Prospek Referral</h3>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Alat Kesehatan</span>
                <div id="modalProduk" class="text-base font-bold text-slate-900"></div>
            </div>

            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Estimasi Pengadaan</span>
                    <span id="modalEstimasi" class="text-sm font-bold text-slate-800"></span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Komisi Member</span>
                    <span id="modalKomisi" class="text-sm font-black text-teal-600"></span>
                    <span id="modalPersen" class="text-xs text-slate-500"></span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Waktu Pengajuan</span>
                    <span id="modalTanggal" class="text-xs text-slate-600"></span>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Status Deal</span>
                    <div id="modalStatusBadge" class="mt-0.5"></div>
                </div>
            </div>

            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Catatan Faskes / Spesifikasi</span>
                <div id="modalCatatan" class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200 whitespace-pre-line leading-relaxed"></div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end">
            <button onclick="closeDetailModal()" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
function showDetailModal(data) {
    document.getElementById('modalId').textContent = data.id;
    document.getElementById('modalProduk').textContent = data.produk;
    document.getElementById('modalEstimasi').textContent = data.estimasi;
    document.getElementById('modalKomisi').textContent = data.komisi;
    document.getElementById('modalPersen').textContent = ' (' + data.persen + ')';
    document.getElementById('modalTanggal').textContent = data.tanggal;
    document.getElementById('modalCatatan').textContent = data.catatan;

    const badgeContainer = document.getElementById('modalStatusBadge');
    if (data.status === 'Deal') {
        badgeContainer.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full"><i class="fa-solid fa-circle-check text-[10px]"></i> Deal</span>';
    } else if (data.status === 'Pending') {
        badgeContainer.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full"><i class="fa-solid fa-clock text-[10px]"></i> Pending Follow-up</span>';
    } else {
        badgeContainer.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 bg-slate-200 px-2.5 py-0.5 rounded-full"><i class="fa-solid fa-circle-xmark text-[10px]"></i> Batal</span>';
    }

    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

// Tutup modal jika klik di luar box
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
