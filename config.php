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
    $nama_member = $member_data['nama_lengkap'] ?? 'Member Visi Academy';
    $kode_member = $member_data['kode_member'] ?? '-';
    $institusi   = !empty($member_data['institusi']) ? $member_data['institusi'] : '-';
    $no_telp     = !empty($member_data['no_telp']) ? $member_data['no_telp'] : '-';

    $nama_produk     = $deal_data['nama_produk'] ?? '-';
    $estimasi_nilai  = format_rupiah($deal_data['estimasi_nilai'] ?? 0);
    $persen_komisi   = number_format((float)($deal_data['persen_komisi'] ?? 3), 2);
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
             "Silakan tim marketing memvalidasi dan menindaklanjuti prospek ini di ERP Kantor:\n" .
             "👉 https://office.visiyosindo.id/member_visiacademy\n\n" .
             "_Sistem Office PT Visi Yosindo Medikal_";

    return send_wa_group_marketing($pesan);
}
