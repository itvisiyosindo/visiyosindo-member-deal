<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Program Referral Komisi Alat Kesehatan';
require_once __DIR__ . '/header.php';
?>

<div class="py-6 sm:py-12">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-navy-900 via-slate-900 to-teal-950 rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden mb-10 border border-slate-800">
        <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="max-w-3xl relative z-10">
            <span class="inline-flex items-center gap-2 bg-teal-500/20 text-teal-300 text-xs font-semibold px-3 py-1.5 rounded-full border border-teal-500/30 mb-6">
                <i class="fa-solid fa-award"></i> Program Kemitraan Visi Academy
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight mb-4">
                Portal Referral & Komisi Alat Kesehatan
            </h1>
            <p class="text-slate-300 text-base sm:text-lg mb-8 leading-relaxed">
                Khusus Dokter, Radiografer, dan Alumni Pelatihan Visi Academy. Sambungkan kebutuhan pengadaan alat kesehatan di Rumah Sakit atau Faskes Anda dengan <strong>PT Visi Yosindo Medikal</strong> dan dapatkan komisi referral transparan.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="register.php" class="bg-teal-600 hover:bg-teal-500 text-white font-bold px-6 py-3.5 rounded-xl shadow-lg shadow-teal-600/30 transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Gabung Jadi Member
                </a>
                <a href="login.php" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold px-6 py-3.5 rounded-xl border border-slate-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Portal
                </a>
            </div>
        </div>
    </div>

    <!-- 3 Langkah Mudah -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-teal-500/50 transition">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-xl mb-4">
                1
            </div>
            <h3 class="font-bold text-slate-900 text-lg mb-2">Daftar Akun Member</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Registrasi mandiri secara cepat menggunakan No. WhatsApp & data profesi/instansi faskes Anda.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-teal-500/50 transition">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xl mb-4">
                2
            </div>
            <h3 class="font-bold text-slate-900 text-lg mb-2">Ajukan Kebutuhan Alkes</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Input informasi pengadaan atau kebutuhan alat kesehatan (USG, X-Ray, CT, C-Arm, Apron, dll) di RS/Klinik Anda.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-teal-500/50 transition">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl mb-4">
                3
            </div>
            <h3 class="font-bold text-slate-900 text-lg mb-2">Pantau & Cairkan Komisi</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Pantau proses verifikasi tim sales Visi Yosindo dan terima komisi langsung ke rekening bank saat transaksi deal.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
