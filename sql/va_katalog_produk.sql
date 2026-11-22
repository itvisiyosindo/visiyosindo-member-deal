-- Migrasi Tabel Katalog Produk Alkes & Brosur
-- Database: visiyosi_office
-- Terintegrasi dengan Portal Member Visi Academy (katalog.php) & Admin Sistem Office

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Awal Produk Alat Kesehatan Populer PT Visi Yosindo Medikal
INSERT INTO `va_katalog_produk`
  (`nama_produk`, `kategori`, `deskripsi_singkat`, `estimasi_harga`, `url_gambar`, `url_brosur`, `status`, `urutan`)
SELECT * FROM (
  SELECT 'USG Color Doppler 4D' AS nama_produk, 'USG & Diagnostik' AS kategori, 'Sistem USG Color Doppler 4D resolusi tinggi dengan probe convex, linear, dan transvaginal untuk OBGYN & radiologi.' AS deskripsi_singkat, 350000000.00 AS estimasi_harga, NULL AS url_gambar, NULL AS url_brosur, 'aktif' AS status, 1 AS urutan
  UNION ALL
  SELECT 'USG Portable B/W & Color', 'USG & Diagnostik', 'Unit USG portabel ringkas dan ringan untuk poli klinik, IGD, maupun kunjungan homecare dengan baterai tahan lama.', 125000000.00, NULL, NULL, 'aktif', 2
  UNION ALL
  SELECT 'X-Ray Stationary 500mA High Frequency', 'Radiologi', 'Pesawat sinar-X stasioner kapasitas 500mA High Frequency lengkap dengan meja bucky & bucky stand (siap integrasi DR).', 550000000.00, NULL, NULL, 'aktif', 3
  UNION ALL
  SELECT 'Mobile X-Ray Unit 100mA / 300mA', 'Radiologi', 'Unit radiografi mobile fleksibel untuk ruang ICU, IGD, dan rawat inap dengan manuver mudah di koridor rumah sakit.', 280000000.00, NULL, NULL, 'aktif', 4
  UNION ALL
  SELECT 'C-Arm Digital Radiography', 'Radiologi', 'Sistem fluoroskopi C-Arm digital untuk ruang operasi bedah tulang (ortopedi), urologi, dan tindakan intervensi.', 850000000.00, NULL, NULL, 'aktif', 5
  UNION ALL
  SELECT 'Dental X-Ray & Panoramic OPG', 'Dental & Imaging', 'Pencitraan radiografi gigi periapikal hingga Panoramic OPG digital presisi tinggi untuk klinik gigi dan rumah sakit.', 240000000.00, NULL, NULL, 'aktif', 6
  UNION ALL
  SELECT 'Mammography System', 'Radiologi', 'Sistem mammografi digital resolusi tinggi untuk skrining dan diagnostik kanker payudara dengan dosis radiasi rendah.', 950000000.00, NULL, NULL, 'aktif', 7
  UNION ALL
  SELECT 'Lead Apron & Proteksi Radiasi (Kaca Timbal / Thyroid)', 'Proteksi Radiasi', 'Perlengkapan proteksi radiasi standar BAPETEN: Lead Apron 0.5mm Pb, Thyroid Shield, Kacamata Pb, dan Kaca Timbal ruang X-Ray.', 8500000.00, NULL, NULL, 'aktif', 8
  UNION ALL
  SELECT 'Patient Monitor Multi-Parameter', 'ICU & Monitoring', 'Monitor pasien 5–7 parameter (ECG, SpO2, NIBP, RESP, TEMP) layar sentuh warna untuk ICU, OK, dan ruang rawat.', 28000000.00, NULL, NULL, 'aktif', 9
  UNION ALL
  SELECT 'Electrocardiograph (EKG / ECG 12 Channel)', 'Kardiologi & Poli', 'Alat rekam jantung EKG 12-lead digital lengkap dengan interpretasi otomatis, printer thermal, dan penyimpanan data.', 18500000.00, NULL, NULL, 'aktif', 10
) AS seed_data
WHERE NOT EXISTS (SELECT 1 FROM `va_katalog_produk` LIMIT 1);
