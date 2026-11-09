# Laporan Investigasi & Perbaikan Bug: Pembayaran & Dashboard Admin (`murid@bimbelku.com`)

## 1. Ringkasan Eksekutif
Berdasarkan investigasi menyeluruh menggunakan akun murid demo (`murid@bimbelku.com`), ditemukan dua akar permasalahan utama:
1. **Masalah Pembayaran**: Pesanan berhasil dibuat, namun ketika murid melanjutkan ke halaman pembayaran, sistem menampilkan pesan *"Hubungi admin"* karena parameter `xendit_configured` bernilai `false` (mengingat `XENDIT_ENABLED=false` pada lingkungan lokal/testing) dan logika di frontend sebelumnya membatasi opsi pembayaran hanya via Xendit tanpa mengecek ketersediaan metode pembayaran manual/QRIS.
2. **Masalah Dashboard Admin**: Pesanan paket belajar baru milik murid demo berstatus `pending` dan memiliki `payment_proof = null` tidak muncul pada daftar verifikasi pesanan/pembayaran admin. Hal ini disebabkan oleh kueri filter di `AdminFinanceOperationsController::payments()` yang secara ketat mewajibkan `payment_proof IS NOT NULL` ATAU status berada di luar `pending`.

Seluruh masalah telah diperbaiki, diuji, dan diverifikasi berhasil tanpa melanggar batasan keamanan produksi.

---

## 2. Detail Akar Masalah & Solusi

### A. Masalah Pembayaran (Frontend & Konfigurasi)
- **Akar Masalah**: 
  - Pada `PaymentPage.tsx`, pengecekan metode pembayaran hanya mengandalkan `settings.provider === "xendit"` tanpa memvalidasi apakah Xendit benar-benar terkonfigurasi (`xendit_configured`). Akibatnya, ketika Xendit mati/nonaktif, blok UI manual payment (QRIS / Transfer Bank) tidak terjangkau atau tertutup oleh pesan error.
- **Solusi**:
  - Memperbarui kondisi pengecekan di `PaymentPage.tsx` (`settings.provider === "xendit" && settings.xendit_configured`) agar jika Xendit tidak aktif, antarmuka pembayaran manual transfer/QRIS langsung terbuka dengan benar menggunakan data `PaymentSetting` yang tersimpan di database.

### B. Masalah Dashboard Admin (Backend Controller)
- **Akar Masalah**:
  - Di `AdminFinanceOperationsController::payments()`, terdapat kueri filter:
    ```php
    $orders->whereNotNull('payment_proof')
           ->orWhereIn('status', ['paid', 'rejected', 'refund_pending', 'refunded']);
    ```
    Kueri ini mengecualikan pesanan baru berstatus `pending` yang belum mengunggah bukti bayar atau menggunakan QRIS otomatis.
- **Solusi**:
  - Memperbarui kueri filter pada `AdminFinanceOperationsController.php` agar pesanan dengan status `pending` (atau status aktif lainnya) tetap dapat tampil di daftar verifikasi pembayaran admin sehingga admin dapat memantau pesanan masuk secara real-time.

---

## 3. Hasil Pengujian & Verifikasi
1. **Build Frontend**: Berhasil dijalankan (`npm run build`) tanpa error, seluruh asset terkompilasi dengan bersih.
2. **Unit & Feature Test Backend**:
   - `PaymentJourneyDemoTest`: Berhasil melewati 7 tes skenario pembayaran end-to-end.
   - `AdminFinanceAndTeacherScheduleTest`: Berhasil melewati seluruh assertion laporan keuangan dan jadwal admin.
3. **Kondisi Database**:
   - Record pesanan murid demo (`User ID: 3`, `Order ID: 1`, `PKG-20261030200022-1-13`) kini dapat diakses dengan benar baik dari sisi dashboard siswa maupun dashboard admin.

---

## 4. Kesimpulan
Alur pemesanan, pembayaran manual/QRIS, serta pengelolaan pesanan di dashboard admin kini telah pulih dan berfungsi dengan normal dan aman sesuai spesifikasi fungsional QA.
