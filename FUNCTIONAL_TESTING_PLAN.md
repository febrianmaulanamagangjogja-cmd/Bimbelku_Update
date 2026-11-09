# FUNCTIONAL_TESTING_PLAN.md

Dokumen ini memuat panduan lengkap, persiapan lingkungan lokal, daftar akun pengujian, dan urutan skenario pengujian fungsional untuk sistem BimbelKu.

---

## 1. Daftar Akun Pengujian & Kredensial Aman

Seluruh kredensial akun uji dibuat secara otomatis melalui `DatabaseSeeder` dan `AdminSeeder` ketika `SEED_DEMO_USERS=true` diaktifkan pada lingkungan lokal.

| Peran (Role)        | Email Akun Uji                                                           | Cara Mendapatkan / Mengatur Password                                                | Catatan Akses                                                                                        |
| :------------------ | :----------------------------------------------------------------------- | :---------------------------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------- |
| **Admin Utama**     | Ditentukan lewat `PRIMARY_ADMIN_EMAIL` atau `SEED_ADMIN_EMAIL` di `.env` | Diatur melalui variabel `SEED_ADMIN_PASSWORD` (minimal 12 karakter) di file `.env`. | Akses penuh ke dashboard admin, audit keuangan, manajemen tutor, dan verifikasi pembayaran.          |
| **Tutor / Teacher** | `kenji@guru.com`                                                         | Password default demo: `password123` (dikonfigurasi dalam `DatabaseSeeder`).        | Akses dashboard tutor, manajemen jadwal, penerimaan tawaran les, dan pencatatan kehadiran.           |
| **Murid / Student** | `murid@bimbelku.com`                                                     | Password default demo: `password123` (dikonfigurasi dalam `DatabaseSeeder`).        | Akses pencarian tutor, pemesanan paket belajar, pembayaran QRIS/Xendit sandbox, dan dashboard murid. |

minho@guru.com, password123, siti@murid.com, password123
_Catatan Keamanan:_ Jangan pernah menampilkan password hash atau token rahasia produksi dalam dokumentasi ini atau repository publik.

---

## 2. Persiapan Lingkungan Lokal

1. **Prasyarat Sistem**:
   - Node.js (v24+) & npm
   - PHP (8.2+) dengan ekstensi standar Laravel (PDO, Mbstring, BCMath, OpenSSL)
   - MySQL / MariaDB (disarankan versi 10.4+)
2. **Konfigurasi Backend (`bimbelku-backend/.env`)**:
   - Pastikan database mengarah ke database QA: `DB_DATABASE=bimbelku_test`
   - Pastikan variabel demo aktif: `SEED_DEMO_USERS=true`
   - Pastikan gateway sandbox aktif tanpa Xendit production: `XENDIT_ENABLED=true`, `XENDIT_SECRET_KEY=test_key_sandbox`
3. **Konfigurasi Frontend**:
   - Pastikan `VITE_API_BASE_URL` mengarah ke backend lokal pada port `8000` (`http://127.0.0.1:8000/api` atau `/api` melalui proxy Vite).
4. **Menjalankan Server Lokal**:
   - **Backend**: `cd bimbelku-backend && php artisan serve --port=8000`
   - **Frontend**: `npm run dev` (berjalan di port Vite default `5173`, berkomunikasi ke backend `8000`).
5. **Queue, Scheduler, Storage, & Mail**:
   - **Queue**: `QUEUE_CONNECTION=sync` (untuk pengujian instan) atau `database` dengan `php artisan queue:work`.
   - **Scheduler**: Dijalankan secara otomatis lewat `php artisan schedule:work` untuk memantau kedaluwarsa pembayaran, penawaran tutor, dan sinkronisasi status.
   - **Storage**: `FILESYSTEM_DISK=public` (jalankan `php artisan storage:link`).
   - **Mail**: `MAIL_MAILER=log` (log email tersimpan di `storage/logs/laravel.log`).

---

## 3. Urutan Skenario Pengujian Fungsional

### A. Skenario Pengujian Murid (Student)

1. **Registrasi & Login**:
   - Buka halaman `/login`, masukkan email `murid@bimbelku.com` dan password `password123`.
   - **Expected Result**: Login berhasil, diarahkan ke dashboard murid (`/student/dashboard`).
2. **Pencarian & Pemesanan Tutor / Paket**:
   - Pilih menu Cari Tutor / Paket Belajar, tentukan mata pelajaran (Matematika) dan lokasi/mode online.
   - Lakukan pemesanan paket atau kelas.
   - **Expected Result**: Order terbuat dengan status `pending`, tanggal kedaluwarsa pembayaran (`expires_at` / `seat_expires_at`) dihitung dengan benar dan bukan waktu sekarang otomatis.
3. **Pembayaran & Simulasi Xendit Sandbox**:
   - Lanjutkan ke halaman pembayaran paket. Simulasikan pembayaran sukses.
   - **Expected Result**: Status order berubah menjadi `paid`, saldo/kuota paket murid bertambah.

### B. Skenario Pengujian Tutor (Teacher)

1. **Login & Manajemen Jadwal**:
   - Login dengan email `budi@guru.com` dan password `password123`.
   - Buka menu jadwal dan atur ketersediaan (availability) hari Senin dan Rabu.
   - **Expected Result**: Ketersediaan tersimpan dan tampil di mesin pencari tutor.
2. **Penerimaan Permintaan Les (Teacher Offer)**:
   - Terima notifikasi permintaan les dari murid. Pilih "Terima" sebelum batas waktu (`expires_at`).
   - **Expected Result**: Tawaran berhasil dikonfirmasi, sistem menjadwalkan sesi kelas privat/grup.

### C. Skenario Pengujian Admin

1. **Login Admin Utama**:
   - Login menggunakan email admin utama yang dikonfigurasi via env dengan password `SEED_ADMIN_PASSWORD`.
   - **Expected Result**: Masuk ke panel admin (`/admin`).
2. **Audit & Verifikasi**:
   - Periksa daftar transaksi keuangan, audit log, dan verifikasi pembayaran manual/refund.
   - **Expected Result**: Data transaksi tersaji lengkap, log aktivitas tercatat dengan benar.

---

## 4. Skenario Khusus & Keamanan

- **Skenario Matching Tutor**: Memastikan pencarian tutor mendistribusikan penawaran berurutan dan kedaluwarsa otomatis jika tutor tidak merespons dalam batas waktu (`expires_at`).
- **Skenario Pembayaran & Refund**: Validasi bahwa pembatalan atau refund diproses dengan aman melalui `RefundSettlementService` tanpa menyentuh gateway produksi.
- **Skenario Keamanan Hak Akses**: Memastikan pengguna dengan role `student` tidak dapat mengakses route atau API `/admin` maupun `/teacher` (Protected Route verification).
- **Skenario Scheduler & Queue**: Menjalankan `php artisan bookings:expire` untuk memvalidasi bahwa record dengan `expires_at` lampau ditangani dengan benar (berubah status menjadi `expired` atau dibatalkan otomatis) tanpa error `NULL` value.

---

## 5. Cara Mencatat Bug & Hasil Pengujian

1. **Pencatatan Error**: Jika ditemukan kegagalan pada fungsionalitas, catat langkah reproduksi (Steps to Reproduce), respons HTTP (misal 422, 500), serta log terkait di `bimbelku-backend/storage/logs/laravel.log`.
2. **Regression Test**: Jalankan `cd bimbelku-backend && php artisan test` untuk memastikan setiap perbaikan tidak merusak 386+ test suite yang ada.
3. **Dokumentasi Hasil**: Perbarui laporan pada `AUDIT_MIGRATION_FIX_REPORT.md` atau laporkan ringkasan hasil pengujian aktual.
