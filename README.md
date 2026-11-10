# System School Class Progress (Presensi & Jurnal Sekolah)
> **Sistem Informasi Manajemen Presensi Siswa, Jurnal Mengajar Guru, dan Monitoring KBM Berbasis Web & Mobile**  
> Disusun untuk Memenuhi Standar Kompetensi Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak (RPL) 2026/2027 Paket 3.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Flutter](https://img.shields.io/badge/Flutter-3.x-02569B?style=for-the-badge&logo=flutter&logoColor=white)](https://flutter.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

---

## 📌 Daftar Isi
1. [Tentang Sistem](#-tentang-sistem)
2. [Arsitektur & Tumpukan Teknologi](#-arsitektur--tumpukan-teknologi)
3. [Fitur Utama Sistem](#-fitur-utama-sistem)
4. [Struktur Role & Hak Akses Pengguna](#-struktur-role--hak-akses-pengguna)
5. [Logika Bisnis Khusus (Domain Rules)](#-logika-bisnis-khusus-domain-rules)
6. [Struktur Direktori Repository](#-struktur-direktori-repository)
7. [Panduan Instalasi & Menjalankan Sistem](#-panduan-instalasi--menjalankan-sistem)
8. [Akun Pengujian (Testing Credentials)](#-akun-pengujian-testing-credentials)
9. [Cakupan Matriks Pengujian UKK (27 Test Cases)](#-cakupan-matriks-pengujian-ukk-27-test-cases)
10. [Deployment & Tautan Unduh APK](#-deployment--tautan-unduh-apk)

---

## 📖 Tentang Sistem

**System School Class Progress (SCP)** adalah solusi terintegrasi untuk mendigitalkan proses Kegiatan Belajar Mengajar (KBM) di sekolah menengah kejuruan (SMK). Sistem ini memecahkan berbagai tantangan operasional harian:
- **Presensi Siswa Real-time:** Guru mengabsen siswa langsung di kelas per jam pelajaran tatap muka.
- **Jurnal Mengajar Digital:** Guru mendokumentasikan capaian materi dan mengunggah foto suasana kelas sebagai bukti otentik KBM.
- **Siklus 2 Mingguan (Putaran 1 & Putaran 2):** Mendukung manajemen jadwal Kurikulum Merdeka dengan pembagian siklus putaran ganjil-genap secara transparan.
- **Alur Izin & Sakit Terverifikasi:** Siswa/orang tua mengajukan izin secara digital dengan lampiran bukti surat resmi; diverifikasi secara berjenjang oleh Wali Kelas dan Guru BK.
- **Auto-Sinkronisasi Presensi:** Siswa yang izin/sakitnya telah disetujui otomatis tercatat pada daftar presensi jurnal guru tanpa input manual berulang.
- **Transparansi Wali Murid:** Orang tua dapat memantau kehadiran dan perkembangan belajar anak secara real-time dari aplikasi mobile.
- **Rekap & Pelaporan Resmi:** Menyediakan rekap bulanan dan semesteran dengan persentase kehadiran, ekspor format PDF, dan spreadsheet Excel siap cetak.

---

## 🛠 Arsitektur & Tumpukan Teknologi

Sistem dibangun menggunakan pendekatan **Client-Server terpisah (Decoupled Architecture)** dengan database terpusat:

```
┌────────────────────────────────────────────────────────┐
│                   DATABASE (MySQL)                     │
│    Strict Foreign Keys • Cascading • Audit Logging     │
└───────────────────────────┬────────────────────────────┘
                            │
       ┌────────────────────┴────────────────────┐
       │                                         │
┌──────▼──────────────────┐           ┌──────────▼───────────────┐
│     WEB PORTAL          │           │     RESTful API ENGINE   │
│  Laravel 11 + Blade     │           │  Sanctum Bearer Auth     │
│  Tailwind CSS           │           │  JSON Payload & Uploads  │
│  (Waka, Rekap, Admin)   │           │  Rate Limiting & CORS    │
└─────────────────────────┘           └──────────┬───────────────┘
                                                 │
                                      ┌──────────▼───────────────┐
                                      │    MOBILE APP (Flutter)  │
                                      │  Android (Guru, Wali,    │
                                      │  BK, Siswa, Orang Tua)   │
                                      └──────────────────────────┘
```

### 1. Web Portal & Backend API (`scp_web`)
- **Backend Framework:** Laravel 11.x (PHP 8.2+)
- **Autentikasi:** Laravel Sanctum (Session Stateful untuk Web + Personal Access Tokens untuk API Mobile).
- **Keamanan Password:** Hashed menggunakan algoritma `Bcrypt` (cost factor 12).
- **Frontend Web:** Blade Templating Engine, Tailwind CSS, Alpine.js, Lucide Icons.
- **Ekspor Dokumen:** `barryvdh/laravel-dompdf` (Cetak PDF) dan `maatwebsite/excel` (Ekspor Spreadsheet).
- **Otomasi Deploy:** Webhook CI/CD dengan auto-migrate, storage symlink, dan cache-clearing otomatis.

### 2. Aplikasi Mobile (`scp_mobile`)
- **Framework:** Flutter 3.x (Dart 3.x)
- **Target OS:** Android (Mendukung ABI: `arm64-v8a`, `armeabi-v7a`, `x86_64`).
- **Tema Desain:** *Warm Theme* (Dark Espresso `#1E1B18`, Creamy Milk `#FBF8F5`, Warm Mocha `#8D7B68`, Sage Green `#4E6C50`).
- **Konektivitas:** Auto-discovery server URL, offline safe-fallback, dan penanganan timeout jaringan.

---

## 🌟 Fitur Utama Sistem

### A. Waka Kurikulum (Portal Web)
- **Dashboard Eksekutif:** Statistik KBM harian, ringkasan pengisian jurnal, dan persentase kehadiran siswa sekolah.
- **Manajemen Jadwal Multi-Putaran:** Pengaturan jadwal mengajar untuk **Putaran 1** dan **Putaran 2**.
- **Deteksi Bentrok Jadwal Otomatis (Collision Detection):** Memvalidasi tumpang tindih waktu pada ruang atau kelas yang sama (`Start_A < End_B AND End_A > Start_B`).
- **Penguncian Jadwal (*Lock Schedule*):** Membekukan jadwal secara global. Tombol tambah/edit/hapus di UI nonaktif, dan seluruh HTTP request backend dilindungi middleware (merespons `403 Forbidden` jika jadwal terkunci).
- **Peralihan Siklus KBM (*Toggle Cycle*):** Mengganti siklus aktif antar Putaran 1 dan Putaran 2 secara instan yang langsung berdampak ke seluruh tampilan web dan mobile.
- **Fitur Hapus Semua Jadwal (*Reset/Simulation*):** Tombol pembersihan seluruh jadwal dengan dialog konfirmasi aman dan pembersihan dependensi foreign key tanpa merusak struktur database.
- **Smart Matrix Importer:** Dukungan pengelompokan otomatis jadwal pelajaran dan matriks jadwal ruang berdasarkan jam pelajaran sebagai kunci relasi.

### B. Guru Pengajar (Aplikasi Mobile)
- **Jadwal Harian & Mingguan Terkunci:** Jadwal mengajar otomatis tersaring sesuai siklus aktif yang ditentukan Waka Kurikulum.
- **Pengisian Jurnal KBM:** Ringkasan materi, capaian pembelajaran, dan catatan khusus kelas.
- **Upload Dokumentasi Foto Kelas:** Bukti foto suasana KBM langsung dari kamera ponsel atau galeri dengan validasi ekstensi gambar (`jpg`, `jpeg`, `png`) dan batasan ukuran maksimal.
- **Presensi Siswa Terintegrasi:** Opsi presensi Hadir (H), Izin (I), Sakit (S), dan Alpa (A).
- **Auto-Sync Pengajuan Izin:** Siswa yang izinnya disetujui otomatis berstatus I/S pada form presensi.
- **Notifikasi Pengingat (*Alert Reminder*):** Peringatan visual jika guru belum mengisi jurnal dalam toleransi 30 menit setelah jam mengajar usai. Indikator otomatis hilang seketika setelah jurnal disimpan.

### C. Wali Kelas & Guru BK (Web & Mobile)
- **Verifikasi Pengajuan Izin Siswa:** Menerima permohonan izin/sakit siswa binaan, memeriksa dokumen/surat bukti, dan memberikan status *Approved* atau *Rejected*.
- **Batasan Pengajuan:** Sistem membatasi maksimal 1 pengajuan per siswa per hari kalender untuk mencegah duplikasi.
- **Rekap Presensi Komprehensif:** Rekapitulasi per bulan dan per semester dengan persentase kehadiran aktual.
- **Early Warning BK:** Indikator khusus untuk memantau siswa dengan akumulasi alpa tinggi.

### D. Siswa & Orang Tua (Aplikasi Mobile)
- **Pemantauan KBM:** Jadwal pelajaran kelas harian dan informasi ruang belajar.
- **Pengajuan Izin Mandiri:** Formulir izin/sakit dengan kewajiban unggah dokumen pendukung (surat dokter / surat dinas orang tua).
- **Riwayat Kehadiran Real-time:** Mengetahui status kehadiran harian secara transparan.

---

## 👥 Struktur Role & Hak Akses Pengguna

| Role | Kode Sistem | Akses Web | Akses Mobile | Cakupan Hak Akses Utama |
|---|---|:---:|:---:|---|
| **Waka Kurikulum** | `waka_kurikulum` | Ya | Ya | Kelola Master Data, Jadwal KBM, Lock Schedule, Toggle Siklus, Monitoring Jurnal & Absensi Seluruh Sekolah. |
| **Guru Pengajar** | `guru` | Ya | Ya | Melihat Jadwal Mengajar Siklus Aktif, Input & Edit Jurnal, Presensi Siswa, Dokumentasi Foto KBM. |
| **Wali Kelas** | `wali_kelas` | Ya | Ya | Hak akses Guru + Verifikasi Izin Siswa Binaan, Rekapitulasi Presensi Kelas Binaan (PDF/Excel). |
| **Guru BK** | `guru_bk` | Ya | Ya | Monitoring Absensi Seluruh Jurusan, Verifikasi Izin Lanjutan, Rekapitulasi Semester Sekolah. |
| **Siswa** | `siswa` | - | Ya | Monitoring Jadwal Kelas, Pengajuan Izin/Sakit Mandiri, Melihat Riwayat Absensi Pribadi. |
| **Orang Tua** | `orang_tua` | - | Ya | Monitoring Kehadiran Anak, Pengajuan Izin Anak, Riwayat Presensi Real-time. |

---

## 📐 Logika Bisnis Khusus (Domain Rules)

### 1. Relasi Jadwal Pelajaran & Matriks Ruangan
Penetapan ruang KBM menggunakan prinsip pencocokan komposit:
$$\text{Ruang} = f(\text{Siklus}, \text{Hari}, \text{Jam Pelajaran}, \text{Kelas})$$
- Jam pelajaran (misal: *Jam 1: 08.00 - 08.35*) pada file Jadwal Pelajaran menjadi **kunci referensi utama** untuk mengambil kode ruangan dari matriks jadwal ruang.
- Normalisasi kode ruang: `12` $\rightarrow$ Ruang 12, `L.16` $\rightarrow$ Lab Komputer 16, `HALL` $\rightarrow$ Ruang Pertemuan/Seni, `OR` $\rightarrow$ Lapangan Olahraga.

### 2. Validasi Anti-Bentrok Jadwal (Conflict Prevention)
Setiap jadwal baru diverifikasi terhadap jadwal eksisting pada siklus yang sama:
$$\text{Bentrok} \iff (\text{Hari}_A = \text{Hari}_B) \land (\text{Ruang}_A = \text{Ruang}_B \lor \text{Kelas}_A = \text{Kelas}_B) \land (\text{Start}_A < \text{End}_B \land \text{End}_A > \text{Start}_B)$$

### 3. Penguncian Jadwal (*Lock Schedule*)
- Ketika flag `is_schedule_locked = true` pada tabel `settings`, seluruh aksi mutasi jadwal (`store`, `update`, `destroy`, `destroyAll`) akan langsung ditolak oleh backend dengan respons HTTP `403 Forbidden`.

---

## 📂 Struktur Direktori Repository

```
SCP_project/
├── scp_web/                          # Source Code Web Portal & API Backend (Laravel)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/              # RESTful API Controllers (Mobile)
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── JournalController.php
│   │   │   │   │   ├── LeaveRequestController.php
│   │   │   │   │   └── TeacherDashboardController.php
│   │   │   │   └── Web/              # Web Controllers (Waka, Rekap, Jadwal)
│   │   │   │       ├── ScheduleController.php
│   │   │   │       └── WakaDashboardController.php
│   │   │   └── Middleware/           # Role Authorization & Schedule Lock Guard
│   │   └── Models/                   # Eloquent Data Models
│   ├── database/
│   │   ├── migrations/               # Struktur Skema Database
│   │   └── seeders/                  # Seeder Akun, Master Data & Jadwal Riil
│   ├── public/
│   │   └── downloads/                # Distribusi APK Mobile Release
│   ├── resources/views/              # Blade Views & Layouts (Tailwind)
│   └── routes/
│       ├── api.php                   # Definisi Endpoint API Mobile & Webhook
│       └── web.php                   # Definisi Rute Web Portal
│
└── scp_mobile/                       # Source Code Aplikasi Mobile (Flutter)
    ├── lib/
    │   ├── config/                   # Konfigurasi API Endpoint & Warm Theme
    │   ├── screen/                   # Screen Antarmuka Aplikasi
    │   │   ├── dashboard_screen.dart # Dashboard Terpadu Guru, Wali, Siswa
    │   │   ├── journal_screen.dart   # Form Jurnal KBM & Presensi Siswa
    │   │   ├── leave_request_screen.dart
    │   │   └── verify_leave_screen.dart
    │   └── main.dart                 # Titik Masuk Utama Aplikasi Flutter
    └── pubspec.yaml                  # Dependensi Paket Flutter
```

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### Persyaratan Lingkungan
- PHP $\ge$ 8.2 (Ekstensi: `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`)
- Composer $\ge$ 2.5
- Node.js $\ge$ 18.x & NPM
- MySQL $\ge$ 8.0 / MariaDB $\ge$ 10.4
- Flutter SDK $\ge$ 3.19.x & Android Studio (untuk kompilasi mobile)

### 1. Setup Backend & Web (`scp_web`)
```bash
# 1. Masuk ke direktori web
cd scp_web

# 2. Salin environment file
cp .env.example .env

# 3. Konfigurasi kredensial database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ukk_scp
DB_USERNAME=root
DB_PASSWORD=

# 4. Install dependensi PHP
composer install

# 5. Generate application encryption key
php artisan key:generate

# 6. Jalankan migrasi dan isi data awal seeder
php artisan migrate:fresh --seed

# 7. Hubungkan storage symlink untuk upload foto
php artisan storage:link

# 8. Install dependensi JS & compile aset frontend
npm install
npm run build

# 9. Jalankan local development server
php artisan serve
```
Web portal dapat diakses di: `http://127.0.0.1:8000`

---

### 2. Setup Aplikasi Mobile (`scp_mobile`)
```bash
# 1. Masuk ke direktori mobile
cd scp_mobile

# 2. Unduh paket dependensi Flutter
flutter pub get

# 3. Jalankan di perangkat emulator / HP fisik terhubung
flutter run

# 4. Atau lakukan build APK rilis
flutter build apk --release --split-per-abi
```

---

## 🔑 Akun Pengujian (Testing Credentials)

Semua akun pengujian menggunakan password default: **`password`**

| Role | Username / Identifier | Nama Pengguna | Catatan Akses |
|---|---|---|---|
| **Waka Kurikulum** | `waka_kurikulum` | Waka Kurikulum, S.Pd | Akses penuh Web Portal Waka (`/waka/*`) |
| **Guru Pengajar** | `rosyida_ramadhani` | ROSYIDA RAMADHANI, S.Pd | Pengajar KK RPL, Jadwal Aktif Putaran 1 & 2 |
| **Wali Kelas** | `dinda_ayu` | DINDA AYU DEWANTI, S.Kom | Wali Kelas XII RPL 2 & Pengajar Mapel Produktif |
| **Guru BK** | `guru_bk` | Guru BK, S.Pd, Kons | Verifikasi Izin Siswa & Monitoring Absensi |
| **Siswa** | `2024001` (NIS) | Siswa RPL 1 | Siswa Kelas X RPL 1 (Kurikulum Merdeka) |
| **Orang Tua** | `ortu_2024001` | Orang Tua Siswa RPL 1 | Terhubung langsung dengan data siswa `2024001` |

---

## 📋 Cakupan Matriks Pengujian UKK (27 Test Cases)

Seluruh 27 skenario pengujian pada instrumen UKK RPL 2026/2027 Paket 3 telah terakomodasi dan tervalidasi:

| Kode Test Case | Kategori | Skenario Pengujian | Status Implementasi |
|---|---|---|:---:|
| **TC-SEC-01** | Security & Auth | Login Multi-Role (Waka, Guru, Wali Kelas, BK, Siswa/Ortu) | ✅ PASS |
| **TC-SEC-02** | Security & Auth | Akses Rekap Jadwal KBM Transparan Lintas Role | ✅ PASS |
| **TC-SEC-03** | Security & Auth | Proteksi URL Khusus Waka (Forbidden 403 Backend Middleware) | ✅ PASS |
| **TC-SEC-04** | Security & Auth | Password Hashing (Bcrypt Algorithm Verification) | ✅ PASS |
| **TC-SEC-05** | Security & Auth | Session Logout & Cache Invalidation | ✅ PASS |
| **TC-SCH-01** | Schedule | Tambah Jadwal Mengajar Valid ke Database | ✅ PASS |
| **TC-SCH-02** | Schedule | Validasi Otomatis Jadwal Bentrok (Ruang & Kelas) | ✅ PASS |
| **TC-SCH-03** | Schedule | Pergantian Siklus KBM (Toggle Putaran 1 & Putaran 2) | ✅ PASS |
| **TC-SCH-04** | Schedule | Lock Schedule di Antarmuka Frontend | ✅ PASS |
| **TC-SCH-05** | Schedule | Proteksi Endpoint Mutasi Jadwal Saat Terkunci (HTTP 403) | ✅ PASS |
| **TC-JRN-01** | Jurnal & Presensi | Input Jurnal Materi KBM & Presensi Siswa (H, I, S, A) | ✅ PASS |
| **TC-JRN-02** | Jurnal & Presensi | Upload Foto Dokumentasi KBM Realtime | ✅ PASS |
| **TC-JRN-03** | Jurnal & Presensi | Validasi Format File Gambar & Batasan Ukuran Upload | ✅ PASS |
| **TC-JRN-04** | Jurnal & Presensi | Edit Jurnal & Presensi Sebelum Jadwal Terkunci | ✅ PASS |
| **TC-IZN-01** | Izin & Sakit | Pengajuan Izin/Sakit Siswa dengan Dokumen Bukti | ✅ PASS |
| **TC-IZN-02** | Izin & Sakit | Verifikasi Pengajuan Izin oleh Wali Kelas & BK (Approve/Reject) | ✅ PASS |
| **TC-IZN-03** | Izin & Sakit | Sinkronisasi Otomatis Izin Terverifikasi ke Form Presensi Guru | ✅ PASS |
| **TC-IZN-04** | Izin & Sakit | Validasi Wajib Lampiran Dokumen Bukti Surat Dokter/Ortu | ✅ PASS |
| **TC-ALT-01** | Alert & Rekap | Peringatan Keterlambatan Pengisian Jurnal (> 30 menit) | ✅ PASS |
| **TC-ALT-02** | Alert & Rekap | Otomatis Menghilangkan Indikator Alert Setelah Jurnal Disimpan | ✅ PASS |
| **TC-ALT-03** | Alert & Rekap | Rekap Presensi Siswa Bulanan & Semesteran | ✅ PASS |
| **TC-ALT-04** | Alert & Rekap | Perhitungan Persentase Kehadiran Aktual Siswa | ✅ PASS |
| **TC-REP-01** | Pelaporan | Filter Rekapitulasi Berdasarkan Kelas, Jurusan, dan Periode | ✅ PASS |
| **TC-REP-02** | Pelaporan | Ekspor Laporan Presensi ke Format PDF Resmi Siap Cetak | ✅ PASS |
| **TC-REP-03** | Pelaporan | Ekspor Laporan Presensi ke Format Excel (.xlsx) | ✅ PASS |
| **TC-REP-04** | Audit Trail | Pencatatan Riwayat Verifikasi Izin dan Perubahan Jurnal | ✅ PASS |
| **TC-REP-05** | Responsivitas | Adaptabilitas Tampilan Web & Mobile Multi-Resolusi | ✅ PASS |

---

## 🌐 Deployment & Tautan Unduh APK

Sistem telah dideploy secara penuh pada infrastruktur cloud hosting produksi:
- **Web Portal Resmi:** [https://classprogress.web.id](https://classprogress.web.id)
- **Endpoint API Mobile:** [https://classprogress.web.id/api](https://classprogress.web.id/api)
- **Halaman Download Aplikasi Mobile:** [https://classprogress.web.id/download/app](https://classprogress.web.id/download/app)
- **Direct Download Release APK:** [https://classprogress.web.id/downloads/SCP-Presensi.apk](https://classprogress.web.id/downloads/SCP-Presensi.apk)

---

## 📄 Lisensi
Hak Cipta © 2026/2027 Tim Pengembang Sistem SCP RPL SMK Negeri 1 Bantul.  
Didistribusikan di bawah lisensi terbuka [MIT License](https://opensource.org/licenses/MIT).
