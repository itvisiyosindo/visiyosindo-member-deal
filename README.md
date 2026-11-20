# Portal Member Visi Academy - PT Visi Yosindo Medikal

Website portal khusus member eksternal (Dokter, Radiografer, dan Alumni Pelatihan Visi Academy) untuk program referral komisi pengadaan alat kesehatan PT Visi Yosindo Medikal.

Sistem ini terintegrasi langsung dengan database ERP kantor (**office.visiyosindo.id**).

---

## 🛠️ Konfigurasi & Struktur Database

- **Database:** `visiyosi_office`
- **Host:** `office.visiyosindo.id` (otomatis fallback ke `localhost` jika dijalankan di server yang sama dengan database ERP)
- **Tabel yang Digunakan:**
  - `va_member`: Data member referral, kode unik member (`VA-xxxx`), login credentials, profil, faskes/institusi asal, dan nomor rekening pencairan komisi.
  - `va_member_deal`: Data prospek kebutuhan alat kesehatan, estimasi nilai pengadaan, persentase komisi, nominal komisi, status deal (`Pending`, `Deal`, `Batal`), serta catatan/spesifikasi.

---

## 🚀 Fitur Utama

1. **Autentikasi Mandiri (Register & Login):**
   - Registrasi member baru dengan validasi No. WhatsApp dan Email unik.
   - Otomatis meng-generate `kode_member` (format `VA-xxxx`).
   - Keamanan password menggunakan algoritma hashing modern (`password_hash` BCRYPT/DEFAULT).
   - Login fleksibel via No. WhatsApp, Email, atau Kode Member + Password.
2. **Dashboard Member:**
   - Ringkasan komisi referral:
     - **Komisi Deal**: Total komisi yang telah disetujui / siap dicairkan.
     - **Estimasi Komisi Pending**: Total potensi komisi dari prospek yang sedang dalam follow-up tim sales.
   - Counter total pengajuan alkes (Deal vs Pending vs Batal).
   - Widget & Alert Rekening Bank: Notifikasi jika data rekening belum diisi untuk pencairan komisi.
   - Tabel 5 pengajuan alkes terbaru.
3. **Form Pengajuan Kebutuhan Alkes Baru (`ajukan-deal.php`):**
   - Pilihan rekomendasi alat kesehatan populer (USG Color Doppler, X-Ray Stationary, Mobile X-Ray, C-Arm, Mammography, Lead Apron, Patient Monitor, EKG, dll).
   - Input faskes tujuan & PIC kontak terkait di RS/Klinik.
   - Input estimasi nilai pengadaan (Rp) dengan format separator ribuan otomatis.
   - Kalkulasi komisi otomatis secara real-time di layar browser.
   - Status awal otomatis tercatat sebagai `Pending`.
4. **Tracking Status Prospek (`tracking-deal.php`):**
   - Tabel tracking komprehensif seluruh pengajuan milik member.
   - Filter status cepat: **Semua**, **Pending**, **Deal**, dan **Batal**.
   - Pencarian berdasarkan nama alkes atau catatan.
   - Modal detail interaktif untuk melihat catatan spesifikasi lengkap dan histori pengajuan.
5. **Manajemen Rekening & Profil (`profil.php`):**
   - Update data rekening bank (Nama Bank, Nomor Rekening, Atas Nama Rekening sesuai buku tabungan).
   - Update data profil (Nama & Gelar, WhatsApp, Email, Institusi Asal).
   - Fitur ganti password dengan verifikasi password lama.

---

## 💻 Menjalankan di Lingkungan Lokal (Development)

Jalankan PHP Built-in Server di folder ini:
```powershell
cd "F:\visiyosindo member deal"
php -S localhost:8000
```
Lalu buka browser di: `http://localhost:8000`

---

## 📁 Struktur File

```
F:\visiyosindo member deal\
├── config.php          # Konfigurasi database PDO, helper session, & formatting
├── header.php          # Template header, navbar, Tailwind CSS, FontAwesome, & flash message
├── footer.php          # Template footer & script responsive mobile menu
├── index.php           # Landing page pengenalan program referral & call-to-action
├── login.php           # Halaman login member (WhatsApp / Email & Password)
├── register.php        # Halaman pendaftaran member baru mandiri
├── dashboard.php       # Dashboard utama member (Ringkasan komisi, stats, info rekening)
├── ajukan-deal.php     # Form pengajuan kebutuhan alat kesehatan baru
├── tracking-deal.php   # Tabel tracking status prospek (Pending / Deal / Batal)
├── profil.php          # Pengaturan profil, data rekening bank, & ganti password
└── logout.php          # Script logout & pembersihan session
```
