<?php
/**
 * Portal Member Visi Academy - PT Visi Yosindo Medikal
 * File Konfigurasi Database, Keamanan, & Helper Sistem
 */

date_default_timezone_set('Asia/Jakarta');

// Muat override kredensial lokal di server (config.local.php tidak di-commit ke Git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Konfigurasi Database & Rahasia
// shortcut: fallback kredensial dipertahankan sementara agar git pull di cPanel tidak downtime; hapus setelah config.local.php terpasang di server & kredensial dirotasi
if (!defined('DB_HOST_LOCAL'))         define('DB_HOST_LOCAL', 'localhost');
if (!defined('DB_HOST_REMOTE'))        define('DB_HOST_REMOTE', 'office.visiyosindo.id');
if (!defined('DB_NAME'))               define('DB_NAME', 'visiyosi_office');
if (!defined('DB_USER'))               define('DB_USER', 'visiyosi_root');
if (!defined('DB_PASS'))               define('DB_PASS', 'q%MDa{uYlcyv');
if (!defined('DB_PORT'))               define('DB_PORT', 3306);

if (!defined('APP_NAME'))              define('APP_NAME', 'Portal Member Visi Academy');
if (!defined('OFFICE_URL'))            define('OFFICE_URL', 'https://office.visiyosindo.id/');
if (!defined('DEFAULT_KOMISI_PERSEN')) define('DEFAULT_KOMISI_PERSEN', 2.00);
if (!defined('MAX_ESTIMASI_NILAI'))    define('MAX_ESTIMASI_NILAI', 1000000000000.0); // Maks Rp 1 Triliun
if (!defined('LINK_MANAGER_PIN'))      define('LINK_MANAGER_PIN', 'VYM2026!');

if (!defined('WA_DEVICE_ID'))          define('WA_DEVICE_ID', 'bbe8ce03e61d0c490e850bce5f3df56c');
if (!defined('WA_GROUP_NAME'))         define('WA_GROUP_NAME', 'MARKETING PT. VYM');
if (!defined('WA_GROUP_JID'))          define('WA_GROUP_JID', '6281261457547-1569293240@g.us');
if (!defined('FONNTE_TOKEN'))          define('FONNTE_TOKEN', 'fBHmpx7vjoVqVzMwawSa');

if (!defined('SUPPORT_WA_NUMBER'))     define('SUPPORT_WA_NUMBER', '');
if (!defined('SUPPORT_WA_DISPLAY'))    define('SUPPORT_WA_DISPLAY', '');

/**
 * Singleton Database Connection (PDO)
 * Prioritas localhost (instan via socket di cPanel), fallback remote untuk dev lokal
 */
function get_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $hosts = [
        ['host' => DB_HOST_LOCAL,  'timeout' => 2],
        ['host' => DB_HOST_REMOTE, 'timeout' => 5],
    ];
    $last_error = '';

    foreach ($hosts as $cfg) {
        try {
            $dsn = "mysql:host={$cfg['host']};port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => $cfg['timeout'],
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            $last_error = $e->getMessage();
        }
    }

    error_log('[Portal Member] DB connection failed: ' . $last_error);
    http_response_code(503);
    die("<div style='font-family:sans-serif;padding:24px;background:#fef2f2;border:1px solid #ef4444;border-radius:12px;max-width:560px;margin:50px auto;color:#991b1b;'>
        <h3 style='margin-top:0;'>Layanan Sedang Sibuk</h3>
        <p style='margin-bottom:0;'>Sistem sementara tidak dapat terhubung ke server database. Silakan coba beberapa saat lagi atau hubungi tim IT PT Visi Yosindo Medikal.</p>
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
 * Ambil data member yang sedang login (di-cache per request agar tidak query berulang)
 */
function current_member($refresh = false) {
    static $cached = null;
    static $cached_id = null;

    if (!is_logged_in()) {
        $cached = null;
        $cached_id = null;
        return null;
    }

    $current_id = (int)$_SESSION['member_id'];
    if (!$refresh && $cached !== null && $cached_id === $current_id) {
        return $cached;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM va_member WHERE id_member = ? LIMIT 1");
    $stmt->execute([$current_id]);
    $member = $stmt->fetch();

    if (!$member || ($member['status'] ?? '') !== 'aktif') {
        $cached = null;
        $cached_id = null;
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?msg=inactive');
        exit;
    }

    $cached = $member;
    $cached_id = $current_id;
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
    if ($time === false) return '-';
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    return date('d', $time) . ' ' . $bulan[(int)date('m', $time)] . ' ' . date('Y H:i', $time);
}

/**
 * Normalisasi nomor WhatsApp: angka saja, awalan 62 -> 0
 */
function normalize_phone($phone) {
    $digits = preg_replace('/\D/', '', (string)$phone);
    if ($digits === '') {
        return '';
    }
    if (strpos($digits, '62') === 0 && strlen($digits) >= 10) {
        $digits = '0' . substr($digits, 2);
    }
    return $digits;
}

/**
 * Normalisasi email ke huruf kecil
 */
function normalize_email($email) {
    return strtolower(trim((string)$email));
}

/**
 * Parse input nilai Rupiah (mendukung format ribuan Indonesia & DECIMAL DB)
 */
function parse_rupiah_input($raw) {
    $str = trim((string)$raw);
    if ($str === '' || strpos($str, '-') !== false) {
        return 0.0;
    }
    $str = preg_replace('/^rp\s*/i', '', $str);
    $str = str_replace(' ', '', $str);

    // Format DECIMAL DB seperti "350000000.00"
    if (preg_match('/^\d+\.\d{1,2}$/', $str)) {
        return (float)$str;
    }
    // Format Indonesia dengan koma desimal: "150.000.000,50"
    if (preg_match('/^[\d.]+,\d{1,2}$/', $str)) {
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
        return (float)$str;
    }

    $digits = preg_replace('/\D/', '', $str);
    return $digits === '' ? 0.0 : (float)$digits;
}

/**
 * Escape wildcard SQL LIKE (%, _, \)
 */
function escape_like($str) {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string)$str);
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

function rotate_csrf() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Rate Limiter sederhana berbasis file per IP + scope (tahan hapus cookie)
 * shortcut: berbasis file pada satu server cPanel; upgrade ke Redis jika memakai multi-server
 */
function _rate_limit_file($scope) {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vym_portal_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    return $dir . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . $ip) . '.json';
}

function rate_limit_check($scope, $max_attempts = 5, $window_seconds = 600) {
    $file = _rate_limit_file($scope);
    if (!is_file($file)) {
        return ['allowed' => true, 'retry_after' => 0];
    }
    $raw = @file_get_contents($file);
    $hits = json_decode((string)$raw, true);
    if (!is_array($hits)) {
        return ['allowed' => true, 'retry_after' => 0];
    }
    $now = time();
    $cutoff = $now - $window_seconds;
    $hits = array_values(array_filter($hits, static function ($ts) use ($cutoff) {
        return is_int($ts) && $ts > $cutoff;
    }));
    if (count($hits) >= $max_attempts) {
        $retry_after = max(1, ($hits[0] + $window_seconds) - $now);
        return ['allowed' => false, 'retry_after' => $retry_after];
    }
    return ['allowed' => true, 'retry_after' => 0];
}

function rate_limit_hit($scope, $window_seconds = 600) {
    $file = _rate_limit_file($scope);
    $now = time();
    $cutoff = $now - $window_seconds;
    $hits = [];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter($decoded, static function ($ts) use ($cutoff) {
                return is_int($ts) && $ts > $cutoff;
            }));
        }
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
}

function rate_limit_reset($scope) {
    $file = _rate_limit_file($scope);
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * Generate Kode Member Unik (VA-XXX), selaras dengan penomoran ERP
 */
function generate_kode_member($db) {
    $next_num = 1;

    $stmt = $db->query("SELECT kode_member FROM va_member WHERE kode_member LIKE 'VA-%' ORDER BY id_member DESC LIMIT 1");
    $last = $stmt->fetch();
    if ($last && preg_match('/^VA-(\d+)$/', (string)$last['kode_member'], $m)) {
        $next_num = (int)$m[1] + 1;
    } else {
        $row = $db->query("SELECT MAX(id_member) as max_id FROM va_member")->fetch();
        $next_num = ($row && $row['max_id']) ? (int)$row['max_id'] + 1 : 1;
    }

    $chk = $db->prepare("SELECT COUNT(*) FROM va_member WHERE kode_member = ?");
    for ($i = 0; $i < 100; $i++) {
        $kode = 'VA-' . str_pad($next_num + $i, 3, '0', STR_PAD_LEFT);
        $chk->execute([$kode]);
        if ((int)$chk->fetchColumn() === 0) {
            return $kode;
        }
    }

    return 'VA-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Kirim Notifikasi WhatsApp ke Grup Marketing PT Visi Yosindo Medikal
 */
function send_wa_group_marketing($message) {
    if (!function_exists('curl_init')) {
        error_log('[Portal Member WA] Ekstensi PHP cURL tidak aktif.');
        return false;
    }

    // 1. Utamakan kirim via Whacenter Group Gateway
    $ch = curl_init('https://app.whacenter.com/api/sendGroup');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'device_id' => WA_DEVICE_ID,
            'group'     => WA_GROUP_NAME,
            'message'   => $message
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res = curl_exec($ch);
    $err1 = curl_error($ch);
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
            'target'  => WA_GROUP_JID,
            'message' => $message,
        ],
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . FONNTE_TOKEN
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $res2 = curl_exec($ch);
    $err2 = curl_error($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode2 >= 200 && $httpCode2 < 300 && $res2) {
        $json2 = json_decode($res2, true);
        if (!is_array($json2) || !isset($json2['status']) || !empty($json2['status'])) {
            return true;
        }
    }

    error_log(sprintf(
        '[Portal Member WA] Gagal kirim ke grup marketing. WhaCenter(HTTP %d, err=%s) | Fonnte(HTTP %d, err=%s)',
        (int)$httpCode,
        $err1 ?: 'none',
        (int)$httpCode2,
        $err2 ?: 'none'
    ));
    return false;
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
