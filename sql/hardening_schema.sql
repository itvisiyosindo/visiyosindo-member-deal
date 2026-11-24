-- Hardening Skema Database Portal Member Visi Academy
-- Database: visiyosi_office
-- Jalankan sekali di phpMyAdmin / MySQL server ERP untuk mendukung UTF-8 penuh (emoji/simbol WA)
-- dan mempercepat pencarian tanpa merusak data existing.

-- 1. Konversi charset tabel va_member & va_member_deal dari latin1 ke utf8mb4
ALTER TABLE `va_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `va_member_deal` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Tambahkan indeks kategori di va_katalog_produk jika belum ada
ALTER TABLE `va_katalog_produk` ADD INDEX `idx_kategori` (`kategori`);

-- 3. (Opsional) Setelah memastikan tidak ada data duplikat/kosong di va_member, aktifkan UNIQUE KEY:
--    Cek dulu: SELECT email, COUNT(*) FROM va_member WHERE email != '' GROUP BY email HAVING COUNT(*) > 1;
--    Cek dulu: SELECT no_telp, COUNT(*) FROM va_member WHERE no_telp != '' GROUP BY no_telp HAVING COUNT(*) > 1;
-- ALTER TABLE `va_member` ADD UNIQUE KEY `uniq_member_email` (`email`);
-- ALTER TABLE `va_member` ADD UNIQUE KEY `uniq_member_telp` (`no_telp`);
