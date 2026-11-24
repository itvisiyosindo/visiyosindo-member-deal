<?php
/**
 * Self-check CLI untuk Portal Member Visi Academy
 * Jalankan: php tests/check.php          (offline unit checks)
 *           php tests/check.php --db     (termasuk cek query read-only ke MySQL)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once dirname(__DIR__) . '/config.php';

$passed = 0;
function check($cond, $label) {
    global $passed;
    if (!$cond) {
        fwrite(STDERR, "[FAIL] {$label}\n");
        exit(1);
    }
    $passed++;
    echo "[PASS] {$label}\n";
}

// 1. Timezone
check(date_default_timezone_get() === 'Asia/Jakarta', 'Timezone PHP adalah Asia/Jakarta');

// 2. Normalisasi nomor WA & email
check(normalize_phone('+62 812-3456-7890') === '081234567890', 'normalize_phone ubah +62 dengan strip/spasi ke 08');
check(normalize_phone('6281234567890') === '081234567890', 'normalize_phone ubah 628 ke 08');
check(normalize_phone('0812 3456 7890') === '081234567890', 'normalize_phone bersihkan spasi');
check(normalize_phone('') === '', 'normalize_phone string kosong');
check(normalize_email('  Dokter@RSUD.ID  ') === 'dokter@rsud.id', 'normalize_email trim & lowercase');

// 3. Parsing Rupiah & kalkulasi komisi
check(parse_rupiah_input('150.000.000') === 150000000.0, 'parse_rupiah_input ribuan Indonesia');
check(parse_rupiah_input('Rp 150.000.000') === 150000000.0, 'parse_rupiah_input dengan prefix Rp');
check(parse_rupiah_input('350000000.00') === 350000000.0, 'parse_rupiah_input DECIMAL DB (.00 tidak dikali 100)');
check(parse_rupiah_input('150.000.000,50') === 150000000.5, 'parse_rupiah_input koma desimal Indonesia');
check(parse_rupiah_input('-50000') === 0.0, 'parse_rupiah_input tolak angka negatif');
check(parse_rupiah_input('abc') === 0.0, 'parse_rupiah_input huruf saja jadi 0');
check(round((350000000.0 * DEFAULT_KOMISI_PERSEN) / 100, 2) === 7000000.0, 'komisi 2% dari 350 juta = 7 juta');

// 4. Formatting & escape LIKE
check(format_rupiah(7000000) === 'Rp 7.000.000', 'format_rupiah normal');
check(format_rupiah(null) === 'Rp 0', 'format_rupiah null jadi Rp 0');
check(format_tanggal_id(null) === '-', 'format_tanggal_id null');
check(format_tanggal_id('2026-11-24 17:05:00') === '24 Nov 2026 17:05', 'format_tanggal_id format WIB');
check(escape_like('100%_test\\a') === '100\\%\\_test\\\\a', 'escape_like melindungi %, _, dan backslash');

// 5. CSRF token & rotasi
$_SESSION = [];
$_POST = [];
$tok1 = csrf_token();
check(strlen($tok1) === 64, 'csrf_token menghasilkan 64 karakter hex');
$_POST['csrf_token'] = $tok1;
check(verify_csrf() === true, 'verify_csrf lolos dengan token valid');
$tok2 = rotate_csrf();
check($tok2 !== $tok1, 'rotate_csrf menghasilkan token baru');
check(verify_csrf() === false, 'token lama ditolak setelah rotate_csrf');

// 6. Rate limiter
$rl_scope = 'selfcheck_' . bin2hex(random_bytes(4));
rate_limit_reset($rl_scope);
check(rate_limit_check($rl_scope, 2, 60)['allowed'] === true, 'rate_limit_check mengizinkan percobaan awal');
rate_limit_hit($rl_scope, 60);
check(rate_limit_check($rl_scope, 2, 60)['allowed'] === true, 'rate_limit_check masih mengizinkan di bawah batas');
rate_limit_hit($rl_scope, 60);
check(rate_limit_check($rl_scope, 2, 60)['allowed'] === false, 'rate_limit_check memblokir setelah mencapai batas');
rate_limit_reset($rl_scope);
check(rate_limit_check($rl_scope, 2, 60)['allowed'] === true, 'rate_limit_reset membuka blokir kembali');

// 7. Urutan aturan keamanan .htaccess
$ht = file_get_contents(dirname(__DIR__) . '/.htaccess');
$pos_block_dot = strpos($ht, 'RewriteRule (^|/)\. - [F,L]');
$pos_block_sql = strpos($ht, 'RewriteRule \.(sql|zip)$ - [F,NC,L]');
$pos_pass_file = strpos($ht, 'RewriteCond %{REQUEST_FILENAME} -f');
check($pos_block_dot !== false && $pos_pass_file !== false && $pos_block_dot < $pos_pass_file, '.htaccess memblokir dotfiles (.git) SEBELUM pass-through -f');
check($pos_block_sql !== false && $pos_block_sql < $pos_pass_file, '.htaccess memblokir .sql/.zip SEBELUM pass-through -f');

// 8. Opsional: cek query read-only terhadap database live
if (in_array('--db', $argv ?? [], true)) {
    $db = get_db();
    check($db instanceof PDO, 'get_db() berhasil terhubung via PDO');

    $like = '%' . escape_like('USG%') . '%';
    $st1 = $db->prepare("SELECT id_produk FROM va_katalog_produk WHERE status = 'aktif' AND (nama_produk LIKE ? ESCAPE '\\\\' OR deskripsi_singkat LIKE ? ESCAPE '\\\\') ORDER BY urutan ASC LIMIT 24 OFFSET 0");
    $st1->execute([$like, $like]);
    check(is_array($st1->fetchAll()), 'Query katalog (ESCAPE + LIMIT/OFFSET) valid di MariaDB/MySQL');

    $st2 = $db->prepare("SELECT id_deal FROM va_member_deal WHERE id_member = ? AND (nama_produk LIKE ? ESCAPE '\\\\' OR catatan LIKE ? ESCAPE '\\\\') ORDER BY created_at DESC LIMIT 25 OFFSET 0");
    $st2->execute([5, $like, $like]);
    check(is_array($st2->fetchAll()), 'Query tracking-deal (ESCAPE + LIMIT/OFFSET) valid di MariaDB/MySQL');

    $st3 = $db->prepare("SELECT COUNT(*) as total_prospek, COALESCE(SUM(CASE WHEN status_deal = 'Deal' THEN nominal_komisi ELSE 0 END), 0) as komisi_deal FROM va_member_deal WHERE id_member = ?");
    $st3->execute([5]);
    check(is_array($st3->fetch()), 'Query statistik dashboard valid');

    $kode = generate_kode_member($db);
    check(preg_match('/^VA-\d{3,}$/', $kode) === 1, "generate_kode_member() valid ({$kode})");
}

echo "\nSemua {$passed} pemeriksaan lulus!\n";
