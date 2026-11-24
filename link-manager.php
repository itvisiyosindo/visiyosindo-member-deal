<?php
require_once __DIR__ . '/config.php';

if (isset($_GET['lm_logout'])) {
    unset($_SESSION['lm_authenticated']);
    header('Location: link-manager.php');
    exit;
}

$lm_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lm_action']) && $_POST['lm_action'] === 'unlock') {
    $rl = rate_limit_check('link_manager_pin', 5, 600);
    if (!verify_csrf()) {
        $lm_error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif (!$rl['allowed']) {
        $menit = (int)ceil($rl['retry_after'] / 60);
        $lm_error = "Terlalu banyak percobaan PIN salah. Coba lagi dalam {$menit} menit.";
    } else {
        $pin_input = trim($_POST['lm_pin'] ?? '');
        if ($pin_input !== '' && hash_equals(LINK_MANAGER_PIN, $pin_input)) {
            rate_limit_reset('link_manager_pin');
            session_regenerate_id(true);
            rotate_csrf();
            $_SESSION['lm_authenticated'] = true;
            header('Location: link-manager.php');
            exit;
        }
        rate_limit_hit('link_manager_pin', 600);
        $lm_error = 'PIN akses salah. Silakan hubungi tim IT PT Visi Yosindo Medikal.';
    }
}

if (empty($_SESSION['lm_authenticated'])):
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akses Terbatas - Visiyosindo Link Manager</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="assets/logo.png">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1.5rem;background:linear-gradient(135deg,#040d1f 0%,#071e46 60%,#0047ba 100%);">
  <div style="background:#ffffff;border-radius:16px;max-width:420px;width:100%;box-shadow:0 20px 35px -10px rgba(0,0,0,0.35);overflow:hidden;border:1px solid #e2e8f0;">
    <div style="background:linear-gradient(135deg,#041128,#073273);padding:1.75rem 1.5rem;text-align:center;color:#ffffff;">
      <div style="width:58px;height:58px;border-radius:14px;background:#ffffff;padding:6px;margin:0 auto 0.75rem;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
        <img src="assets/logo.png" alt="PT Visi Yosindo Medikal" style="width:100%;height:100%;object-fit:contain;">
      </div>
      <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:0.25rem;">Visiyosindo Link Manager</h1>
      <p style="font-size:0.8rem;color:#bfdbfe;">Masukkan PIN internal untuk mengelola tautan singkat.</p>
    </div>

    <form method="POST" action="link-manager.php" style="padding:1.75rem 1.5rem;">
      <?= csrf_field() ?>
      <input type="hidden" name="lm_action" value="unlock">

      <?php if ($lm_error !== ''): ?>
        <div style="margin-bottom:1rem;padding:0.75rem 0.9rem;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.85rem;font-weight:500;">
          <?= htmlspecialchars($lm_error) ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="lm_pin">PIN Akses Admin</label>
        <input type="password" id="lm_pin" name="lm_pin" class="form-input" placeholder="Masukkan PIN akses..." required autofocus>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
        Buka Link Manager
      </button>

      <div style="margin-top:1rem;text-align:center;">
        <a href="/" style="font-size:0.8rem;color:#64748b;text-decoration:none;font-weight:600;">&larr; Kembali ke Portal Member</a>
      </div>
    </form>
  </div>
</body>
</html>
<?php
    exit;
endif;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visiyosindo Link Manager - Kelola & Modifikasi Link</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71'/><path d='M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71'/></svg>">
</head>
<body>

  <!-- Top Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="brand">
        <div class="brand-icon" style="background:#ffffff;padding:3px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-radius:10px;">
          <img src="assets/logo.png" alt="PT Visi Yosindo Medikal" style="width:100%;height:100%;object-fit:contain;">
        </div>
        <div>
          <span class="brand-name">Visiyosindo Link</span>
          <span class="brand-badge current-domain-text">visiyosindo.id</span>
        </div>
      </div>
      <div class="nav-actions">
        <a href="/" class="btn btn-outline btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
          Portal Member
        </a>
        <button id="btn-export" class="btn btn-outline btn-sm" title="Unduh cadangan data JSON">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Cadangan Data
        </button>
        <button id="btn-create-link" class="btn btn-primary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Buat Link Baru
        </button>
        <a href="link-manager.php?lm_logout=1" class="btn btn-outline btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;" title="Kunci kembali akses Link Manager">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          Kunci Akses
        </a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="container">

    <!-- Statistik Ringkas -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value" id="total-links-count">0</div>
          <div class="stat-label">Total Link Aktif</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value" id="total-clicks-count">0</div>
          <div class="stat-label">Total Pengunjung / Klik</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
        </div>
        <div class="stat-info">
          <div class="stat-value" id="top-link-slug" style="font-size: 1.1rem; word-break: break-all;">-</div>
          <div class="stat-label">Link Terpopuler</div>
        </div>
      </div>
    </div>

    <!-- Action Bar (Search & Filter) -->
    <div class="action-bar">
      <div class="search-box">
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="text" id="search-input" class="search-input" placeholder="Cari link berdasarkan slug, judul, atau URL tujuan...">
      </div>
    </div>

    <!-- Tabel Daftar Link -->
    <div class="links-container">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nama & Tautan Singkat</th>
              <th>URL Tujuan Asli</th>
              <th>Klik</th>
              <th>Dibuat</th>
              <th style="text-align: right;">Aksi</th>
            </tr>
          </thead>
          <tbody id="links-table-body">
            <!-- Diisi otomatis oleh JavaScript -->
          </tbody>
        </table>
      </div>

      <!-- State Jika Belum Ada Link -->
      <div id="empty-state" class="empty-state" style="display: none;">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <div class="empty-title">Tidak ada link ditemukan</div>
        <div class="empty-desc">Mulai buat link kustom pertama Anda untuk menggantikan Bitly!</div>
      </div>
    </div>

  </main>

  <!-- Modal Buat / Edit Link -->
  <div id="link-modal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-title" class="modal-title">Buat Link Baru</h3>
        <button id="btn-close-modal" class="btn-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      <form id="link-form">
        <div class="modal-body">
          <input type="hidden" id="link-id">

          <div class="form-group">
            <label class="form-label" for="link-title">Judul / Catatan (Opsional)</label>
            <input type="text" id="link-title" class="form-input" placeholder="Contoh: Katalog Promo Akhir Tahun">
          </div>

          <div class="form-group">
            <label class="form-label" for="link-slug">Custom Slug / Alias Link *</label>
            <div class="input-addon-group">
              <span class="input-addon"><span class="current-domain-text">visiyosindo.id</span>/</span>
              <input type="text" id="link-slug" class="form-input" placeholder="Judulyangakumau" required autocomplete="off">
            </div>
            <div class="form-hint">Mendukung huruf besar, huruf kecil, angka, dan strip (-). Spasi otomatis jadi strip.</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="link-url">URL Tujuan Asli (Bisa Diubah Kapan Saja) *</label>
            <input type="url" id="link-url" class="form-input" placeholder="https://example.com/link-sangat-panjang..." required>
            <div class="form-hint">Pengunjung yang membuka link Anda akan langsung dialihkan ke alamat ini.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" id="btn-cancel-modal" class="btn btn-outline">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Link</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal QR Code -->
  <div id="qr-modal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">QR Code Link</h3>
        <button id="btn-close-qr-modal" class="btn-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="qr-preview-box">
          <div id="qrcode-container"></div>
          <div id="qr-link-text" class="qr-link-text"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="btn-download-qr" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          Unduh Gambar QR Code
        </button>
      </div>
    </div>
  </div>

  <!-- Modal Konfirmasi Hapus -->
  <div id="delete-modal" class="modal-overlay">
    <div class="modal" style="max-width: 420px;">
      <div class="modal-header">
        <h3 class="modal-title">Hapus Link?</h3>
      </div>
      <div class="modal-body">
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Apakah Anda yakin ingin menghapus tautan <strong id="delete-slug-text" style="color: var(--secondary);"></strong>? Tautan ini tidak akan dapat diakses lagi oleh pengunjung.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" id="btn-cancel-delete" class="btn btn-outline">Batal</button>
        <button type="button" id="btn-confirm-delete" class="btn btn-primary" style="background: var(--danger);">Hapus Sekarang</button>
      </div>
    </div>
  </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast">Notifikasi</div>

  <!-- Footer -->
  <footer>
    &copy; 2026 Visiyosindo Link Manager &bull; Mandiri, Cepat, Tanpa Limit.
  </footer>

  <!-- Scripts -->
  <!-- QRCode.js Library via CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <!-- Supabase JS Client via CDN (untuk Cloud Database di GitHub Pages) -->
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <!-- Konfigurasi -->
  <script src="js/config.js?v=9"></script>
  <!-- Modul Penyimpanan -->
  <script src="js/storage.js?v=9"></script>
  <!-- Logika Dashboard -->
  <script src="js/app.js?v=9"></script>
</body>
</html>
