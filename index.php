<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Program Referral Komisi Alat Kesehatan';
require_once __DIR__ . '/header.php';
?>

<div class="py-6 sm:py-10">
    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-navy-950 via-vym-900 to-vym-800 rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden mb-10 border border-vym-800/50">
        <!-- Background Glow -->
        <div class="absolute -right-10 -bottom-10 w-96 h-96 bg-vym-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-80 h-80 bg-vym-400/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div class="max-w-3xl relative z-10">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-white p-1.5 shadow-lg flex items-center justify-center">
                    <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
                </div>
                <span class="inline-flex items-center gap-2 bg-vym-500/20 text-vym-300 text-xs font-semibold px-3 py-1.5 rounded-full border border-vym-400/30">
                    <i class="fa-solid fa-award text-vym-300"></i> Program Kemitraan Visi Academy
                </span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight mb-4">
                Portal Referral & Komisi Alat Kesehatan
            </h1>
            <p class="text-slate-200 text-base sm:text-lg mb-8 leading-relaxed">
                Khusus Dokter, Radiografer, dan Alumni Pelatihan Visi Academy. Sambungkan kebutuhan pengadaan alat kesehatan di Rumah Sakit atau Faskes Anda dengan <strong>PT Visi Yosindo Medikal</strong> dan dapatkan komisi referral transparan.
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="register.php" class="bg-vym-600 hover:bg-vym-500 text-white font-bold px-6 py-3.5 rounded-xl shadow-lg shadow-vym-600/40 transition flex items-center gap-2">
                    <i class="fa-solid fa-user-plus"></i> Daftar Sekarang
                </a>
                <a href="login.php" class="bg-slate-900/80 hover:bg-slate-800 text-slate-200 font-semibold px-6 py-3.5 rounded-xl border border-slate-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            </div>
        </div>
    </div>

    <!-- 3 Langkah Mudah -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-vym-400/60 transition group">
            <div class="w-12 h-12 rounded-xl bg-vym-50 text-vym-600 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-105 transition-transform">
                1
            </div>
            <h3 class="font-bold text-slate-900 text-lg mb-2">Daftar Akun</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Registrasi mandiri secara cepat menggunakan No. WhatsApp & data profesi/instansi faskes Anda.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-vym-400/60 transition group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-105 transition-transform">
                2
            </div>
            <h3 class="font-bold text-slate-900 text-lg mb-2">Ajukan Kebutuhan Alkes</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Input informasi pengadaan atau kebutuhan alat kesehatan (USG, X-Ray, CT, C-Arm, Apron, dll) di RS/Klinik Anda.
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-vym-400/60 transition group">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl mb-4 group-hover:scale-105 transition-transform">
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
