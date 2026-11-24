<?php
/**
 * Contoh Konfigurasi Rahasia Lokal (JANGAN commit config.local.php ke Git!)
 * Salin file ini menjadi config.local.php di server cPanel, lalu isi dengan kredensial yang sudah dirotasi.
 */

define('DB_HOST_LOCAL', 'localhost');
define('DB_HOST_REMOTE', 'office.visiyosindo.id');
define('DB_NAME', 'visiyosi_office');
define('DB_USER', 'visiyosi_root');
define('DB_PASS', 'GANTI_PASSWORD_DB_BARU');
define('DB_PORT', 3306);

define('LINK_MANAGER_PIN', 'GANTI_PIN_ADMIN_BARU');
define('WA_DEVICE_ID', 'GANTI_DEVICE_ID_WHACENTER');
define('WA_GROUP_NAME', 'MARKETING PT. VYM');
define('WA_GROUP_JID', '6281261457547-1569293240@g.us');
define('FONNTE_TOKEN', 'GANTI_TOKEN_FONNTE');

// Nomor WhatsApp bantuan resmi (format internasional tanpa +, contoh: 6281234567890)
// Biarkan kosong jika tidak ingin menampilkan nomor di footer
define('SUPPORT_WA_NUMBER', '');
define('SUPPORT_WA_DISPLAY', '');
