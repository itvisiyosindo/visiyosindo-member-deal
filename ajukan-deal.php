<?php
require_once __DIR__ . '/config.php';
require_login();

$member = current_member();
$error = '';
$form = [
    'nama_produk' => '',
    'instansi_tujuan' => '',
    'estimasi_nilai' => '',
    'persen_komisi' => '3.00',
    'contact_person' => '',
    'spesifikasi' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $form['instansi_tujuan'] = trim($_POST['instansi_tujuan'] ?? '');
    $raw_estimasi = str_replace(['Rp', '.', ' ', ','], '', $_POST['estimasi_nilai'] ?? '0');
    $form['estimasi_nilai'] = (float)$raw_estimasi;
    $form['persen_komisi'] = (float)($_POST['persen_komisi'] ?? 3.00);
    $form['contact_person'] = trim($_POST['contact_person'] ?? '');
    $form['spesifikasi'] = trim($_POST['spesifikasi'] ?? '');

    if (empty($form['nama_produk'])) {
        $error = 'Nama alat kesehatan / produk wajib diisi.';
    } elseif ($form['estimasi_nilai'] <= 0) {
        $error = 'Estimasi nilai pengadaan harus lebih dari 0.';
    } else {
        // Hitung nominal komisi
        $nominal_komisi = ($form['estimasi_nilai'] * $form['persen_komisi']) / 100;

        // Gabungkan catatan agar lengkap dan mudah dibaca oleh ERP kantor
        $catatan_parts = [];
        if (!empty($form['instansi_tujuan'])) {
            $catatan_parts[] = "Faskes Tujuan: " . $form['instansi_tujuan'];
        }
        if (!empty($form['contact_person'])) {
            $catatan_parts[] = "PIC/Kontak: " . $form['contact_person'];
        }
        if (!empty($form['spesifikasi'])) {
            $catatan_parts[] = "Kebutuhan/Spesifikasi: " . $form['spesifikasi'];
        }
        $catatan_final = implode("\n", $catatan_parts);

        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO va_member_deal 
            (id_member, nama_produk, estimasi_nilai, persen_komisi, nominal_komisi, status_deal, catatan, created_at)
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())
        ");

        try {
            $stmt->execute([
                $member['id_member'],
                $form['nama_produk'],
                $form['estimasi_nilai'],
                $form['persen_komisi'],
                $nominal_komisi,
                $catatan_final
            ]);

            // Kirim notifikasi WA otomatis ke Grup Marketing PT Visi Yosindo Medikal
            $deal_data = [
                'nama_produk'     => $form['nama_produk'],
                'estimasi_nilai'  => $form['estimasi_nilai'],
                'persen_komisi'   => $form['persen_komisi'],
                'nominal_komisi'  => $nominal_komisi,
                'instansi_tujuan' => $form['instansi_tujuan'],
                'contact_person'  => $form['contact_person'],
                'spesifikasi'     => $form['spesifikasi']
            ];
            @notify_marketing_new_deal($deal_data, $member);

            set_flash('success', "Prospek kebutuhan alkes \"{$form['nama_produk']}\" berhasil diajukan! Notifikasi telah dikirimkan ke Tim Marketing kami untuk segera ditindaklanjuti.");
            header('Location: tracking-deal.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan pengajuan prospek: ' . $e->getMessage();
        }
    }
}

$page_title = 'Ajukan Kebutuhan Alkes Baru';
require_once __DIR__ . '/header.php';
?>

<div class="max-w-3xl mx-auto my-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-navy-950 via-vym-900 to-vym-800 p-6 sm:p-8 text-white">
            <div class="flex items-center gap-3.5 mb-2">
                <div class="w-12 h-12 rounded-2xl bg-white p-1.5 flex items-center justify-center shadow-md flex-shrink-0">
                    <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Form Pengajuan Kebutuhan Alkes</h1>
                    <p class="text-xs text-vym-200">Daftarkan prospek kebutuhan alat kesehatan untuk mendapatkan komisi referral</p>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="ajukan-deal.php" method="POST" id="dealForm" class="space-y-6">
                <!-- Info Produk -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                        <i class="fa-solid fa-microscope text-vym-600"></i> Detail Alat Kesehatan
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nama Alat Kesehatan / Kebutuhan <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nama_produk" list="alkesSuggestions" value="<?= htmlspecialchars($form['nama_produk']) ?>" required
                                placeholder="Ketik atau pilih rekomendasi (misal: USG Doppler 4D, X-Ray Stationary 500mA, C-Arm)"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                            <datalist id="alkesSuggestions">
                                <option value="USG Color Doppler 4D">
                                <option value="USG Portable B/W & Color">
                                <option value="X-Ray Stationary 500mA High Frequency">
                                <option value="Mobile X-Ray Unit 100mA / 300mA">
                                <option value="C-Arm Digital Radiography">
                                <option value="Dental X-Ray & Panoramic OPG">
                                <option value="Mammography System">
                                <option value="Lead Apron & Proteksi Radiasi (Kaca Timbal / Thyroid)">
                                <option value="Patient Monitor Multi-Parameter">
                                <option value="Defibrillator AED / Manual">
                                <option value="Electrocardiograph (EKG / ECG 12 Channel)">
                                <option value="Autoclave & Mesin Sterilisasi RS">
                                <option value="Lampu Operasi & Meja Operasi Elektrik">
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Rumah Sakit / Klinik / Faskes Tujuan
                            </label>
                            <input type="text" name="instansi_tujuan" value="<?= htmlspecialchars($form['instansi_tujuan']) ?>"
                                placeholder="Contoh: RSUD Daerah X / Klinik Utama Sehat Mandiri"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                        </div>
                    </div>
                </div>

                <!-- Estimasi Nilai & Komisi -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                        <i class="fa-solid fa-calculator text-vym-600"></i> Nilai Pengadaan & Estimasi Komisi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Estimasi Nilai Pengadaan (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 font-bold text-xs">
                                    Rp
                                </span>
                                <input type="text" id="estimasiNilaiInput" name="estimasi_nilai" required
                                    placeholder="Contoh: 150.000.000"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Perkiraan pagu anggaran / harga pengadaan.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Persentase Komisi (%)
                            </label>
                            <div class="relative">
                                <input type="number" step="0.1" min="1" max="10" id="persenKomisiInput" name="persen_komisi" 
                                    value="<?= htmlspecialchars($form['persen_komisi']) ?>"
                                    class="w-full pl-4 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400 font-bold text-xs">
                                    %
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-500 mt-1 block">Standar referral: 2.5% - 5.0% dari nilai deal bersih.</span>
                        </div>
                    </div>

                    <!-- Live Calculation Card -->
                    <div class="mt-4 p-4 rounded-xl bg-gradient-to-r from-vym-50 via-blue-50 to-indigo-50 border border-vym-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-vym-600 text-white flex items-center justify-center text-lg shadow-sm">
                                <i class="fa-solid fa-coins"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-vym-900 uppercase tracking-wider block">Perkiraan Komisi Yang Anda Dapatkan</span>
                                <span class="text-xs text-vym-700">Dihitung otomatis: Nilai Pengadaan &times; Persentase Komisi</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span id="calculatedNominal" class="text-xl sm:text-2xl font-black text-vym-700">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Kontak PIC & Spesifikasi -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-200 mb-4">
                        <i class="fa-solid fa-circle-info text-vym-600"></i> Informasi Tambahan Prospek
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Kontak Person / Pejabat Terkait di Faskes (Opsional)
                            </label>
                            <input type="text" name="contact_person" value="<?= htmlspecialchars($form['contact_person']) ?>"
                                placeholder="Contoh: Pak Herman (Kabid Yanmed) - 0813xxxxxx"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition">
                            <span class="text-[11px] text-slate-500 mt-1 block">Membantu tim Visi Yosindo berkoordinasi langsung dengan pihak berwenang di RS.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Catatan Kebutuhan / Spesifikasi Khusus (Opsional)
                            </label>
                            <textarea name="spesifikasi" rows="3"
                                placeholder="Contoh: Butuh probe convex + linear, rencana pengadaan triwulan 1, butuh izin BAPETEN dan instalasi."
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-vym-600 focus:bg-white transition"><?= htmlspecialchars($form['spesifikasi']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                    <button type="submit" class="w-full sm:flex-1 bg-vym-600 hover:bg-vym-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-vym-600/20 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan Kebutuhan Alkes
                    </button>
                    <a href="dashboard.php" class="w-full sm:w-auto text-center px-6 py-3.5 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50 transition text-sm">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const nilaiInput = document.getElementById('estimasiNilaiInput');
const persenInput = document.getElementById('persenKomisiInput');
const calculatedNominal = document.getElementById('calculatedNominal');

function formatNumberRupiah(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function updateCalculation() {
    const rawVal = nilaiInput.value.replace(/\D/g, '');
    const numVal = parseFloat(rawVal) || 0;
    const persen = parseFloat(persenInput.value) || 0;
    
    if (rawVal) {
        nilaiInput.value = parseInt(rawVal, 10).toLocaleString('id-ID');
    }
    
    const komisi = (numVal * persen) / 100;
    calculatedNominal.textContent = formatNumberRupiah(komisi);
}

nilaiInput.addEventListener('input', updateCalculation);
persenInput.addEventListener('input', updateCalculation);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
