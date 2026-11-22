<?php
/**
 * Portal Member Visi Academy - PT Visi Yosindo Medikal
 * File Konfigurasi Database & Helper Sistem
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
// Mendukung remote office.visiyosindo.id dan fallback ke localhost
define('DB_HOST_REMOTE', 'office.visiyosindo.id');
define('DB_HOST_LOCAL', 'localhost');
define('DB_NAME', 'visiyosi_office');
define('DB_USER', 'visiyosi_root');
define('DB_PASS', 'q%MDa{uYlcyv');
define('DB_PORT', 3306);

// Nama Aplikasi & URL
define('APP_NAME', 'Portal Member Visi Academy');
define('COMPANY_NAME', 'PT Visi Yosindo Medikal');
define('BASE_URL', './');
define('OFFICE_URL', 'https://office.visiyosindo.id/');
define('DEFAULT_KOMISI_PERSEN', 2.00);
define('LINK_MANAGER_PIN', 'VYM2026!');

/**
 * Singleton Database Connection (PDO)
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $hosts = [DB_HOST_REMOTE, DB_HOST_LOCAL];
    $last_error = '';

    foreach ($hosts as $host) {
        try {
            $dsn = "mysql:host={$host};port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            $last_error = $e->getMessage();
        }
    }

    // Jika gagal terhubung ke kedua host
    die("<div style='font-family:sans-serif;padding:20px;background:#fef2f2;border:1px solid #ef4444;border-radius:8px;max-width:600px;margin:50px auto;color:#991b1b;'>
        <h3 style='margin-top:0;'>Gagal Terhubung ke Database</h3>
        <p>Sistem tidak dapat terhubung ke server database (office.visiyosindo.id / localhost).</p>
        <p><small>Pesan Error: " . htmlspecialchars($last_error) . "</small></p>
    </div>");
}

/**
 * Cek status login
 */
function is_logged_in() {
    return isset($_SESSION['member_id']) && !empty($_SESSION['member_id']);
}

/**
 * Wajib login, redirect ke login.php jika belum login
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Ambil data member yang sedang login
 */
function current_member() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM va_member WHERE id_member = ? LIMIT 1");
    $stmt->execute([$_SESSION['member_id']]);
    $member = $stmt->fetch();

    if (!$member || $member['status'] !== 'aktif') {
        // Jika akun dihapus atau dinonaktifkan
        session_destroy();
        header('Location: login.php?msg=inactive');
        exit;
    }

    return $member;
}

/**
 * Format Angka ke Rupiah
 */
function format_rupiah($angka) {
    if ($angka === null || $angka === '') {
        $angka = 0;
    }
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format Tanggal Indonesia
 */
function format_tanggal_id($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    return date('d', $time) . ' ' . $bulan[(int)date('m', $time)] . ' ' . date('Y H:i', $time);
}

/**
 * Flash Message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * CSRF Token Helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && is_string($session_token) && $session_token !== '' && hash_equals($session_token, $token);
}

/**
 * Pastikan tabel va_katalog_produk tersedia dan terisi seed awal
 */
function ensure_katalog_table($db) {
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `va_katalog_produk` (
                `id_produk` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `nama_produk` VARCHAR(200) NOT NULL,
                `kategori` VARCHAR(100) NOT NULL DEFAULT 'Radiologi',
                `deskripsi_singkat` TEXT NULL,
                `estimasi_harga` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `url_gambar` VARCHAR(255) NULL,
                `url_brosur` VARCHAR(255) NULL,
                `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
                `urutan` INT NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id_produk`),
                KEY `idx_status_urutan` (`status`, `urutan`),
                KEY `idx_kategori` (`kategori`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $count = (int)$db->query("SELECT COUNT(*) FROM va_katalog_produk")->fetchColumn();
        if ($count === 0) {
            $seeds = [
                ['USG Color Doppler 4D', 'USG & Diagnostik', 'Sistem USG Color Doppler 4D resolusi tinggi dengan probe convex, linear, dan transvaginal untuk OBGYN & radiologi.', 350000000.00, null, null, 'aktif', 1],
                ['USG Portable B/W & Color', 'USG & Diagnostik', 'Unit USG portabel ringkas dan ringan untuk poli klinik, IGD, maupun kunjungan homecare dengan baterai tahan lama.', 125000000.00, null, null, 'aktif', 2],
                ['X-Ray Stationary 500mA High Frequency', 'Radiologi', 'Pesawat sinar-X stasioner kapasitas 500mA High Frequency lengkap dengan meja bucky & bucky stand (siap integrasi DR).', 550000000.00, null, null, 'aktif', 3],
                ['Mobile X-Ray Unit 100mA / 300mA', 'Radiologi', 'Unit radiografi mobile fleksibel untuk ruang ICU, IGD, dan rawat inap dengan manuver mudah di koridor rumah sakit.', 280000000.00, null, null, 'aktif', 4],
                ['C-Arm Digital Radiography', 'Radiologi', 'Sistem fluoroskopi C-Arm digital untuk ruang operasi bedah tulang (ortopedi), urologi, dan tindakan intervensi.', 850000000.00, null, null, 'aktif', 5],
                ['Dental X-Ray & Panoramic OPG', 'Dental & Imaging', 'Pencitraan radiografi gigi periapikal hingga Panoramic OPG digital presisi tinggi untuk klinik gigi dan rumah sakit.', 240000000.00, null, null, 'aktif', 6],
                ['Mammography System', 'Radiologi', 'Sistem mammografi digital resolusi tinggi untuk skrining dan diagnostik kanker payudara dengan dosis radiasi rendah.', 950000000.00, null, null, 'aktif', 7],
                ['Lead Apron & Proteksi Radiasi (Kaca Timbal / Thyroid)', 'Proteksi Radiasi', 'Perlengkapan proteksi radiasi standar BAPETEN: Lead Apron 0.5mm Pb, Thyroid Shield, Kacamata Pb, dan Kaca Timbal ruang X-Ray.', 8500000.00, null, null, 'aktif', 8],
                ['Patient Monitor Multi-Parameter', 'ICU & Monitoring', 'Monitor pasien 5–7 parameter (ECG, SpO2, NIBP, RESP, TEMP) layar sentuh warna untuk ICU, OK, dan ruang rawat.', 28000000.00, null, null, 'aktif', 9],
                ['Electrocardiograph (EKG / ECG 12 Channel)', 'Kardiologi & Poli', 'Alat rekam jantung EKG 12-lead digital lengkap dengan interpretasi otomatis, printer thermal, dan penyimpanan data.', 18500000.00, null, null, 'aktif', 10],
            ];

            $ins = $db->prepare("
                INSERT INTO va_katalog_produk
                (nama_produk, kategori, deskripsi_singkat, estimasi_harga, url_gambar, url_brosur, status, urutan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($seeds as $row) {
                $ins->execute($row);
            }
        }
    } catch (Throwable $e) {
        // Jangan hentikan aplikasi jika DB belum memiliki hak DDL atau tabel sedang sibuk
    }
}

/**
 * Generate Kode Member Unik (VA-XXXX)
 */
function generate_kode_member($db) {
    // Ambil ID tertinggi
    $stmt = $db->query("SELECT MAX(id_member) as max_id FROM va_member");
    $row = $stmt->fetch();
    $next_id = ($row && $row['max_id']) ? (int)$row['max_id'] + 1 : 1;

    // Coba format VA-0001
    for ($i = 0; $i < 100; $i++) {
        $candidate_id = $next_id + $i;
        $kode = 'VA-' . str_pad($candidate_id, 3, '0', STR_PAD_LEFT);
        
        $chk = $db->prepare("SELECT COUNT(*) FROM va_member WHERE kode_member = ?");
        $chk->execute([$kode]);
        if ($chk->fetchColumn() == 0) {
            return $kode;
        }
    }
    // Fallback dengan random suffix
    return 'VA-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Kirim Notifikasi WhatsApp ke Grup Marketing PT Visi Yosindo Medikal
 */
function send_wa_group_marketing($message) {
    $devId       = 'bbe8ce03e61d0c490e850bce5f3df56c';
    $groupName   = 'MARKETING PT. VYM';
    $groupJid    = '6281261457547-1569293240@g.us';
    $fonnteToken = 'fBHmpx7vjoVqVzMwawSa';

    // 1. Utamakan kirim via Whacenter Group Gateway
    $ch = curl_init('https://app.whacenter.com/api/sendGroup');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'device_id' => $devId,
            'group'     => $groupName,
            'message'   => $message
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['status'])) {
            return true;
        }
    }

    // 2. Fallback via Fonnte Group Gateway jika Whacenter berhalangan
    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'target'  => $groupJid,
            'message' => $message,
        ],
        CURLOPT_HTTPHEADER     => [
            "Authorization: {$fonnteToken}"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode2 >= 200 && $httpCode2 < 300);
}

/**
 * Notifikasi Pengajuan Kebutuhan Alkes Baru ke Grup Marketing
 */
function notify_marketing_new_deal($deal_data, $member_data) {
    $nama_member = $member_data['nama_member'] ?? $member_data['nama_lengkap'] ?? 'Member Visi Academy';
    $kode_member = $member_data['kode_member'] ?? '-';
    $institusi   = !empty($member_data['institusi_asal']) ? $member_data['institusi_asal'] : (!empty($member_data['institusi']) ? $member_data['institusi'] : '-');
    $no_telp     = !empty($member_data['no_telp']) ? $member_data['no_telp'] : '-';

    $nama_produk     = $deal_data['nama_produk'] ?? '-';
    $estimasi_nilai  = format_rupiah($deal_data['estimasi_nilai'] ?? 0);
    $persen_komisi   = number_format((float)($deal_data['persen_komisi'] ?? DEFAULT_KOMISI_PERSEN), 2);
    $nominal_komisi  = format_rupiah($deal_data['nominal_komisi'] ?? 0);
    $instansi_tujuan = !empty($deal_data['instansi_tujuan']) ? $deal_data['instansi_tujuan'] : '-';
    $contact_person  = !empty($deal_data['contact_person']) ? $deal_data['contact_person'] : '-';
    $spesifikasi     = !empty($deal_data['spesifikasi']) ? $deal_data['spesifikasi'] : '-';

    $pesan = "🔔 *PENGAJUAN KEBUTUHAN ALKES BARU* 🔔\n" .
             "_Portal Member Visi Academy_\n\n" .
             "Halo Tim Marketing PT Visi Yosindo Medikal,\n" .
             "Telah masuk pengajuan kebutuhan alat kesehatan baru dari member referral:\n\n" .
             "👤 *Member Pengaju*: {$nama_member} ({$kode_member})\n" .
             "🏢 *Institusi Asal*: {$institusi}\n" .
             "📞 *No. WhatsApp*: {$no_telp}\n\n" .
             "📋 *Detail Kebutuhan Alkes*:\n" .
             "• *Nama Alat / Produk*: {$nama_produk}\n" .
             "• *Estimasi Nilai*: {$estimasi_nilai}\n" .
             "• *Estimasi Komisi ({$persen_komisi}%)*: {$nominal_komisi}\n" .
             "• *Faskes Tujuan*: {$instansi_tujuan}\n" .
             "• *PIC / Kontak di Faskes*: {$contact_person}\n" .
             "• *Spesifikasi / Catatan*: {$spesifikasi}\n\n" .
             "Silakan tim marketing memvalidasi dan menindaklanjuti prospek ini di Sistem Office:\n" .
             "👉 https://office.visiyosindo.id/member_visiacademy\n\n" .
             "_Sistem Office PT Visi Yosindo Medikal_";

    return send_wa_group_marketing($pesan);
}

/**
 * Notifikasi Pendaftaran Afiliator / Member Baru ke Grup Marketing
 */
function notify_marketing_new_member($member_data) {
    $nama_member = $member_data['nama_member'] ?? 'Member Visi Academy';
    $kode_member = $member_data['kode_member'] ?? '-';
    $institusi   = !empty($member_data['institusi_asal']) ? $member_data['institusi_asal'] : '-';
    $no_telp     = !empty($member_data['no_telp']) ? $member_data['no_telp'] : '-';
    $email       = !empty($member_data['email']) ? $member_data['email'] : '-';

    $pesan = "🆕 *PENDAFTARAN AFILIATOR / MEMBER BARU* 🆕\n" .
             "_Portal Member Visi Academy_\n\n" .
             "Halo Tim Marketing PT Visi Yosindo Medikal,\n" .
             "Telah bergabung member afiliator baru di Portal Visi Academy:\n\n" .
             "👤 *Nama*: {$nama_member}\n" .
             "🪪 *Kode Member*: {$kode_member}\n" .
             "🏢 *Institusi Asal*: {$institusi}\n" .
             "📞 *No. WhatsApp*: {$no_telp}\n" .
             "✉️ *Email*: {$email}\n\n" .
             "Akun member sudah aktif otomatis dan dapat langsung mengajukan prospek alkes.\n" .
             "Kelola data member di Sistem Office:\n" .
             "👉 https://office.visiyosindo.id/member_visiacademy\n\n" .
             "_Sistem Office PT Visi Yosindo Medikal_";

    return send_wa_group_marketing($pesan);
}
