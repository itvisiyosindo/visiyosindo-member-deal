<?php
require_once __DIR__ . '/config.php';
require_login();

$member = current_member();
$db = get_db();
ensure_katalog_table($db);

$kategori_filter = trim($_GET['kategori'] ?? 'all');
$search = trim($_GET['q'] ?? '');

/**
 * Resolve URL gambar / brosur: jika relatif, gabungkan dengan OFFICE_URL
 */
function resolve_office_asset_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    return rtrim(OFFICE_URL, '/') . '/' . ltrim($url, '/');
}

$categories = [];
$products = [];

try {
    $cat_stmt = $db->query("SELECT DISTINCT kategori FROM va_katalog_produk WHERE status = 'aktif' ORDER BY kategori ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

    $query = "SELECT * FROM va_katalog_produk WHERE status = 'aktif'";
    $params = [];

    if ($kategori_filter !== 'all' && $kategori_filter !== '') {
        $query .= " AND kategori = ?";
        $params[] = $kategori_filter;
    }

    if ($search !== '') {
        $query .= " AND (nama_produk LIKE ? OR deskripsi_singkat LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $query .= " ORDER BY urutan ASC, id_produk ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $categories = [];
    $products = [];
}

$komisi_persen = (float)DEFAULT_KOMISI_PERSEN;

$page_title = 'Katalog Produk Alkes & Brosur';
require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-navy-950 via-vym-900 to-vym-800 rounded-3xl p-6 sm:p-8 text-white shadow-md border border-vym-800/60 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-80 h-80 bg-vym-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 bg-vym-500/20 text-vym-200 text-xs font-bold px-3 py-1 rounded-full border border-vym-400/30 mb-3">
                    <i class="fa-solid fa-percent"></i>
                    <span>Komisi Afiliator Tetap: <?= number_format($komisi_persen, 0) ?>%</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Katalog Produk Alkes & Materi Penawaran
                </h1>
                <p class="text-vym-200 text-xs sm:text-sm mt-1.5 max-w-2xl leading-relaxed">
                    Pilih alat kesehatan PT Visi Yosindo Medikal untuk ditawarkan ke relasi Rumah Sakit atau Klinik Anda. Bagikan penawaran via WhatsApp atau langsung ajukan prospek dengan estimasi komisi <strong><?= number_format($komisi_persen, 0) ?>%</strong>.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                <a href="ajukan-deal.php" class="bg-vym-600 hover:bg-vym-500 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-vym-600/30 transition flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-circle-plus"></i> Ajukan Alkes Custom
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Kategori & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0">
            <a href="katalog.php?kategori=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= ($kategori_filter === 'all' || $kategori_filter === '') ? 'bg-navy-950 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Semua Kategori
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="katalog.php?kategori=<?= urlencode($cat) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap <?= $kategori_filter === $cat ? 'bg-vym-600 text-white shadow-sm' : 'bg-vym-50 text-vym-700 hover:bg-vym-100' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form action="katalog.php" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
            <div class="relative flex-1 lg:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari alat kesehatan..."
                    class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
            </div>
            <?php if ($search !== ''): ?>
                <a href="katalog.php?kategori=<?= urlencode($kategori_filter) ?>" class="text-slate-400 hover:text-slate-600 p-1.5 text-xs" title="Reset Pencarian">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
            <button type="submit" class="bg-navy-950 hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-semibold transition">
                Cari
            </button>
        </form>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3 text-slate-400 text-2xl">
                <i class="fa-solid fa-kit-medical"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Produk tidak ditemukan</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <?= ($search !== '' || $kategori_filter !== 'all') ? 'Coba ubah kata kunci pencarian atau pilih kategori lain.' : 'Belum ada data katalog alkes aktif. Anda tetap dapat mengajukan kebutuhan alkes secara manual.' ?>
            </p>
            <div class="mt-4 flex items-center justify-center gap-3">
                <?php if ($search !== '' || $kategori_filter !== 'all'): ?>
                    <a href="katalog.php" class="text-xs font-bold text-vym-600 hover:underline">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filter
                    </a>
                <?php endif; ?>
                <a href="ajukan-deal.php" class="inline-flex items-center gap-2 bg-vym-600 hover:bg-vym-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-circle-plus"></i> Ajukan Alkes Manual
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $prod):
                $harga = (float)($prod['estimasi_harga'] ?? 0);
                $estimasi_komisi = ($harga * $komisi_persen) / 100;
                $img_url = resolve_office_asset_url($prod['url_gambar'] ?? '');
                $brosur_url = resolve_office_asset_url($prod['url_brosur'] ?? '');

                $wa_lines = [
                    "Halo Dokter / Bapak / Ibu,",
                    "Perkenalkan saya *{$member['nama_member']}* (Mitra Visi Academy - PT Visi Yosindo Medikal).",
                    "",
                    "Apakah di faskes/klinik sedang ada rencana pengadaan *{$prod['nama_produk']}*?",
                ];
                if (!empty($prod['deskripsi_singkat'])) {
                    $wa_lines[] = "Info singkat: {$prod['deskripsi_singkat']}";
                }
                if ($brosur_url !== '') {
                    $wa_lines[] = "Brosur produk: {$brosur_url}";
                }
                $wa_lines[] = "";
                $wa_lines[] = "Jika berkenan, tim *PT Visi Yosindo Medikal* siap bantu konsultasi spesifikasi & penawaran resminya.";
                $wa_url = "https://wa.me/?text=" . rawurlencode(implode("\n", $wa_lines));

                $ajukan_url = "ajukan-deal.php?produk=" . urlencode($prod['nama_produk']) . "&harga=" . urlencode((string)(int)round($harga));
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-vym-300 transition flex flex-col overflow-hidden">
                    <!-- Product Image / Placeholder -->
                    <div class="relative h-44 bg-gradient-to-br from-navy-950 via-vym-900 to-vym-800 flex items-center justify-center overflow-hidden">
                        <?php if ($img_url !== ''): ?>
                            <img src="<?= htmlspecialchars($img_url) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="text-center text-white/80 p-4">
                                <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center mx-auto mb-2 text-2xl text-vym-200">
                                    <i class="fa-solid fa-stethoscope"></i>
                                </div>
                                <span class="text-[11px] font-semibold text-vym-200 tracking-wide uppercase">PT Visi Yosindo Medikal</span>
                            </div>
                        <?php endif; ?>
                        <span class="absolute top-3 left-3 bg-navy-950/85 backdrop-blur-sm text-vym-200 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-vym-500/30">
                            <?= htmlspecialchars($prod['kategori']) ?>
                        </span>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col">
                        <h3 class="text-base font-bold text-slate-900 leading-snug mb-2">
                            <?= htmlspecialchars($prod['nama_produk']) ?>
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4 flex-1">
                            <?= htmlspecialchars($prod['deskripsi_singkat'] ?: 'Alat kesehatan resmi dengan dukungan instalasi, garansi, dan layanan purna jual PT Visi Yosindo Medikal.') ?>
                        </p>

                        <!-- Pricing & Commission Box -->
                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 mb-4 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Estimasi Harga:</span>
                                <span class="font-bold text-slate-800"><?= format_rupiah($harga) ?></span>
                            </div>
                            <div class="flex items-center justify-between text-xs pt-1.5 border-t border-slate-200/80">
                                <span class="text-vym-800 font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-coins text-vym-600"></i> Estimasi Komisi (<?= number_format($komisi_persen, 0) ?>%):
                                </span>
                                <span class="font-black text-vym-700 text-sm"><?= format_rupiah($estimasi_komisi) ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-2">
                            <a href="<?= htmlspecialchars($ajukan_url) ?>"
                                class="w-full bg-vym-600 hover:bg-vym-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-sm transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i> Ajukan Prospek Alat Ini
                            </a>
                            <div class="flex items-center gap-2">
                                <a href="<?= htmlspecialchars($wa_url) ?>" target="_blank" rel="noopener noreferrer"
                                    class="flex-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                                    <i class="fa-brands fa-whatsapp text-sm"></i> Share WA
                                </a>
                                <?php if ($brosur_url !== ''): ?>
                                    <a href="<?= htmlspecialchars($brosur_url) ?>" target="_blank" rel="noopener noreferrer"
                                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-file-pdf text-rose-600"></i> Brosur
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Info Disclaimer -->
    <div class="bg-vym-50 border border-vym-200 rounded-2xl p-4 text-xs text-vym-900 flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-vym-600 text-base mt-0.5"></i>
        <p class="leading-relaxed">
            <strong>Catatan:</strong> Harga dan komisi <strong><?= number_format($komisi_persen, 0) ?>%</strong> di atas adalah estimasi acuan awal untuk mempermudah penawaran ke relasi Anda. Nominal final transaksi dan persetujuan komisi diverifikasi oleh tim Sales & Finance PT Visi Yosindo Medikal saat transaksi dinyatakan <strong>Deal</strong>.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
