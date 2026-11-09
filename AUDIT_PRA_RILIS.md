# Laporan Audit Pra-Rilis BimbelKu Upgrade

Tanggal pemeriksaan: 29 Oktober 2026
Sumber: `Bimbelku_Upgrade-main.zip` yang diberikan untuk audit
Cakupan: pemeriksaan source statis, pemeriksaan sintaks PHP, pemeriksaan kontrak route, serta tinjauan konfigurasi keamanan. Pemeriksaan runtime end-to-end belum dapat diselesaikan di lingkungan audit ini.

## Ringkasan eksekutif

**Status: belum layak dinyatakan siap publish.** Ada perbaikan kode yang sudah diterapkan di salinan kerja ini, tetapi pengujian build frontend, test suite Laravel, alur pembayaran sandbox, dan mayoritas skenario matriks QA belum selesai.

### Perbaikan yang sudah diterapkan

1. **CSP memblokir iframe checkout Xendit.** Sebelumnya kebijakan `default-src 'self'` tidak menyatakan `frame-src`, sehingga frame eksternal mengikuti fallback default dan dapat ditolak browser. Ditambahkan allowlist eksplisit bagi `checkout-ui-gateway.xendit.co` dan `checkout-ui-gateway-prod-dev.xendit.co` pada `index.html` dan `public/.htaccess`. Komponen Xendit menggunakan iframe untuk beberapa input pembayaran. `frame-ancestors 'none'` dipertahankan pada header HTTP Apache; direktif yang sama dihapus dari meta CSP karena tidak diberlakukan oleh browser dari elemen meta.
2. **Folder log Laravel hilang pada source ZIP.** Ditambahkan `bimbelku-backend/storage/logs/.gitignore` agar direktori runtime log tetap hadir pada checkout/ekstraksi bersih.
3. **Referensi tes login sudah kedaluwarsa.** `testing-matrix.md` merujuk `LoginPortalSeparationTest`, sementara tes yang ada sekarang adalah `UnifiedLoginTest`. Referensi diperbarui dan validator matriks lulus.
4. **Audit keamanan bergantung pada Git.** `scripts/check-security-baseline.mjs` awalnya gagal saat dipakai pada source ZIP tanpa `.git`. Script sekarang memakai inventaris file filesystem sebagai fallback sehingga pemeriksaan keamanan bisa dipakai pada arsip source maupun checkout Git.
5. **Panduan deployment diperbarui.** `DEPLOYMENT_SECURITY.md` menjelaskan allowlist iframe Xendit dan kewajiban menerapkan header CSP ekuivalen pada Nginx.

## Hasil pemeriksaan yang berhasil

| Pemeriksaan | Hasil |
|---|---|
| `php -l` untuk seluruh file PHP dalam ZIP | 455 lulus, 0 gagal |
| `scripts/check-php-static.mjs` | 453 file PHP lolos pemeriksaan struktur statis |
| `scripts/check-api-contract.mjs` | 279 action API cocok dengan controller/method |
| `scripts/check-xendit-payment.mjs` | Kontrak source checkout/refund/payout/webhook lulus pemeriksaan statis |
| `scripts/check-laravel-runtime-paths.mjs` setelah perbaikan | Lulus |
| `scripts/check-testing-matrix.mjs` setelah perbaikan | Lulus; 190 skenario tercantum pada 9 tahap |
| `scripts/check-security-baseline.mjs` setelah perbaikan | Lulus; 203 file source/config diperiksa |
| `scripts/check-seo.mjs` | 11 pemeriksaan source SEO lulus |
| `scripts/check-page-routes.mjs` | 95 rute dan 327 tautan literal berhasil dipetakan |

Kelulusan pemeriksaan statis **bukan** bukti semua fitur berjalan di browser atau seluruh transaksi telah berhasil.

## Temuan yang masih menghalangi rilis

### 1. Matriks QA belum lengkap

`testing-matrix.md` mempunyai 190 skenario. Status yang tercatat adalah:

- LULUS: 10
- BELUM: 177
- TERHALANG: 3

Ini merupakan status dalam matriks yang disertakan dalam ZIP, bukan hasil baru dari eksekusi test otomatis. Jalankan dan dokumentasikan setiap skenario yang relevan sebelum menyatakan rilis siap.

### 2. Release gate frontend belum terverifikasi

Instalasi dependensi frontend (`npm ci`) tidak selesai dalam batas waktu lingkungan audit. Karena itu `npm run lint`, `npm run typecheck`, `npm run build`, pemeriksaan budget performa, dan validasi SEO atas artefak hasil build belum dapat dipastikan lulus. Beberapa pemeriksaan yang memerlukan `node_modules/typescript` gagal karena dependensi tersebut belum tersedia penuh.

### 3. Test Laravel dan integrasi eksternal belum dijalankan

Composer tidak tersedia di lingkungan audit dan tidak ada direktori `vendor/`, sehingga PHPUnit/Laravel tidak dijalankan. Migrasi database, scheduler, worker queue, login Google, SMTP, transaksi Xendit sandbox, webhook sungguhan, refund, dan payout belum diuji secara runtime. Tidak ada kredensial atau koneksi database produksi yang digunakan.

### 4. Beberapa pemeriksaan lama tidak selaras dengan source saat ini

Sejumlah script `scripts/check-*.mjs` masih mengharapkan struktur atau nama endpoint lama, misalnya pemeriksaan checkpoint yang mencari route payout lama atau `TicketController` yang tidak ada lagi. Beberapa skrip juga memerlukan artefak build atau dependency yang belum tersedia. Jangan menafsirkan setiap kegagalan string-check sebagai bug perilaku; tinjau tiap assertion, perbarui tes supaya menguji kontrak aktual, dan pertahankan pemeriksaan regresi untuk fungsi yang masih didukung.

### 5. Pembayaran produksi perlu dikonfirmasi

Template `bimbelku-backend/.env.production.example` menetapkan `XENDIT_ENABLED=false` dan secret kosong. Ini aman sebagai default, tetapi checkout gateway tidak akan siap produksi hanya dengan mengunggah source. Pemilik produk harus memastikan jalur pembayaran yang dipakai, mengonfigurasi secret di server secara privat, memverifikasi webhook, lalu menyelesaikan pembayaran/refund sandbox sebelum mengaktifkan live mode.

### 6. Berkas controller yatim perlu ditinjau

Ada `app/Http/Controllers/Api/AdminClassController.php` di root repository, di luar aplikasi Laravel yang berlokasi di `bimbelku-backend/`. File ini tampak sebagai salinan lama dan tidak berada di direktori aplikasi yang ditunjuk konfigurasi Composer backend. File tidak dihapus pada audit ini karena perlu konfirmasi pemilik apakah memang artefak lama.

## Langkah berikut yang disarankan

1. Jalankan `npm ci` pada mesin developer atau CI yang punya akses registry paket, lalu jalankan `npm run lint`, `npm run typecheck`, `npm run check`, dan `npm run build`. Kategorikan kegagalan menjadi bug aplikasi, tes usang, atau blocker lingkungan.
2. Jalankan `composer install` di backend, lalu PHPUnit dengan konfigurasi tes yang tersedia dan database SQLite/in-memory atau database QA yang terisolasi; jangan memakai database produksi.
3. Uji tiga peran (murid, tutor, admin), alur booking/matching, penawaran tutor, semua status sesi, pembayaran manual/gateway, webhook duplikat, pembayaran terlambat, refund, pencairan, upload dokumen, scheduler, serta queue worker.
4. Lakukan pemeriksaan browser checkout di domain HTTPS aktual; pastikan console tidak melaporkan pelanggaran CSP dan alur pembayaran sandbox berhasil dari awal hingga webhook.
5. Perbarui matriks 190 skenario berdasarkan bukti pengujian, lakukan backup dan verifikasi pemulihan, kemudian lakukan review pra-rilis dan persetujuan pemilik.

## Batas audit

Pemeriksaan ini merupakan audit statis terbatas pada source ZIP yang diberikan. Tidak dilakukan pentest jaringan, scan dependensi lengkap, pemeriksaan kredensial server, uji beban, atau transaksi uang nyata. Jangan menganggap dokumen ini sebagai sertifikasi keamanan atau persetujuan deployment.
